<?php

namespace App\Platform\Seo\Filament\Resources;

use App\Platform\Identity\Contracts\Actor;
use App\Platform\Seo\Enums\SeoEntityType;
use App\Platform\Seo\Filament\Resources\SeoMetaResource\Pages;
use App\Platform\Seo\Models\SeoMeta;
use App\Platform\Seo\Rules\UniqueCanonical;
use App\Platform\Seo\Rules\ValidCanonical;
use App\Platform\Seo\Rules\ValidJsonLd;
use App\Platform\Seo\Services\SeoResolver;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * The centralized SEO Manager editor — one record per addressable surface (entity_type + entity_key)
 * holding OPTIONAL overrides merged by the SeoResolver over entity + branding defaults. Bilingual
 * meta/OG/Twitter fields, robots toggles, sitemap controls, a validated canonical (safe URL +
 * duplicate detection) and validated JSON-LD, plus a live SERP preview, a canonical/slug preview and
 * non-blocking warnings (missing title/description/image). Admin/super-admin gated.
 */
class SeoMetaResource extends Resource
{
    protected static ?string $model = SeoMeta::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'SEO Manager';

    protected static ?string $recordRouteKeyName = 'public_id';

    protected static ?string $recordTitleAttribute = 'entity_key';

    /** Gate the whole resource to admins (the panel already requires it; defence in depth). */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof Actor && $user->hasRole(['admin', 'super_admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('SEO')->columnSpanFull()->tabs([
                self::identityTab(),
                self::metaTab(),
                self::socialTab(),
                self::structuredTab(),
                self::sitemapTab(),
                self::previewTab(),
            ]),
        ]);
    }

    private static function identityTab(): Tab
    {
        return Tab::make('Entity')->icon('heroicon-o-identification')->schema([
            Section::make('Target')->columns(2)->schema([
                Select::make('entity_type')->options(SeoEntityType::options())
                    ->default(SeoEntityType::MarketingPage->value)->required()->live()
                    ->helperText('Which surface this record overrides.'),
                TextInput::make('entity_key')->required()->maxLength(191)->live()
                    ->helperText('The slug/public_id (or a fixed key like "homepage" for singletons). Unique per type.'),
                Placeholder::make('path_preview')->label('Resolved path')
                    ->content(fn (Get $get): string => self::pathFor($get))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    private static function metaTab(): Tab
    {
        return Tab::make('Meta')->icon('heroicon-o-magnifying-glass')->schema([
            Section::make('Meta tags')->columns(2)->schema([
                TextInput::make('meta_title.en')->label('Meta title (EN)')->maxLength(255)->live(onBlur: true),
                TextInput::make('meta_title.ar')->label('Meta title (AR)')->maxLength(255),
                Textarea::make('meta_description.en')->label('Meta description (EN)')->rows(2)->maxLength(320)->live(onBlur: true),
                Textarea::make('meta_description.ar')->label('Meta description (AR)')->rows(2)->maxLength(320),
                TextInput::make('keywords')->label('Keywords')->maxLength(500)
                    ->helperText('Comma-separated.')->columnSpanFull(),
                TextInput::make('canonical')->label('Canonical URL/path')->maxLength(2048)->live(onBlur: true)
                    ->helperText('Absolute https:// URL or a site-relative path (/...). Leave blank to default to the entity URL.')
                    ->rules([
                        new ValidCanonical,
                        fn (?SeoMeta $record): UniqueCanonical => new UniqueCanonical($record?->getKey()),
                    ])
                    ->columnSpanFull(),
            ]),
            Section::make('Robots')->columns(2)->schema([
                Toggle::make('robots_index')->label('Allow indexing')->default(true)->inline(false),
                Toggle::make('robots_follow')->label('Allow following links')->default(true)->inline(false),
            ]),
        ]);
    }

    private static function socialTab(): Tab
    {
        return Tab::make('Social')->icon('heroicon-o-share')->schema([
            Section::make('Open Graph')->columns(2)->schema([
                TextInput::make('og_title.en')->label('OG title (EN)')->maxLength(255),
                TextInput::make('og_title.ar')->label('OG title (AR)')->maxLength(255),
                Textarea::make('og_description.en')->label('OG description (EN)')->rows(2)->maxLength(320),
                Textarea::make('og_description.ar')->label('OG description (AR)')->rows(2)->maxLength(320),
                TextInput::make('og_image')->label('OG image URL')->url()->maxLength(2048)->columnSpanFull(),
            ]),
            Section::make('Twitter / X')->columns(2)->schema([
                TextInput::make('twitter_title.en')->label('Twitter title (EN)')->maxLength(255),
                TextInput::make('twitter_title.ar')->label('Twitter title (AR)')->maxLength(255),
                Textarea::make('twitter_description.en')->label('Twitter description (EN)')->rows(2)->maxLength(320),
                Textarea::make('twitter_description.ar')->label('Twitter description (AR)')->rows(2)->maxLength(320),
                TextInput::make('twitter_image')->label('Twitter image URL')->url()->maxLength(2048),
                Select::make('twitter_card')->label('Twitter card')
                    ->options(['summary' => 'Summary', 'summary_large_image' => 'Summary (large image)'])
                    ->default('summary_large_image'),
            ]),
        ]);
    }

    private static function structuredTab(): Tab
    {
        return Tab::make('Structured data')->icon('heroicon-o-code-bracket')->schema([
            Section::make('JSON-LD')->schema([
                Textarea::make('json_ld')->label('JSON-LD')->rows(6)
                    ->helperText('Optional raw JSON-LD (object/array). Validated on save; emitted as <script type="application/ld+json">.')
                    ->rules([new ValidJsonLd])
                    ->formatStateUsing(fn ($state): ?string => self::encodeJson($state))
                    ->dehydrateStateUsing(fn ($state) => self::decodeJson($state)),
            ]),
            Section::make('Breadcrumb & hreflang')->columns(2)->schema([
                Textarea::make('breadcrumb')->label('Breadcrumb (JSON array)')->rows(4)
                    ->helperText('Optional ordered list of { name, url } items.')
                    ->rules([new ValidJsonLd])
                    ->formatStateUsing(fn ($state): ?string => self::encodeJson($state))
                    ->dehydrateStateUsing(fn ($state) => self::decodeJson($state)),
                Textarea::make('hreflang')->label('hreflang (JSON object)')->rows(4)
                    ->helperText('Optional { "en": "/en/...", "ar": "/ar/..." } alternate map.')
                    ->rules([new ValidJsonLd])
                    ->formatStateUsing(fn ($state): ?string => self::encodeJson($state))
                    ->dehydrateStateUsing(fn ($state) => self::decodeJson($state)),
            ]),
        ]);
    }

    private static function sitemapTab(): Tab
    {
        return Tab::make('Sitemap')->icon('heroicon-o-map')->schema([
            Section::make('Sitemap')->columns(3)->schema([
                Toggle::make('sitemap_enabled')->label('Include in sitemap')->default(true)->inline(false),
                TextInput::make('sitemap_priority')->label('Priority')->numeric()->minValue(0)->maxValue(1)->step(0.1)
                    ->helperText('0.0–1.0'),
                Select::make('sitemap_changefreq')->label('Change frequency')->options([
                    'always' => 'always', 'hourly' => 'hourly', 'daily' => 'daily', 'weekly' => 'weekly',
                    'monthly' => 'monthly', 'yearly' => 'yearly', 'never' => 'never',
                ])->placeholder('—'),
            ]),
        ]);
    }

    private static function previewTab(): Tab
    {
        return Tab::make('Preview')->icon('heroicon-o-eye')->schema([
            Section::make('Search result preview')->schema([
                Placeholder::make('serp_preview')->label('')
                    ->content(fn (Get $get): HtmlString => self::serpPreview($get)),
            ]),
            Section::make('Warnings')->schema([
                Placeholder::make('warnings')->label('')
                    ->content(fn (Get $get): HtmlString => self::warningsPreview($get)),
            ]),
        ]);
    }

    // ----- Preview / helper builders -----

    private static function pathFor(Get $get): string
    {
        $type = SeoEntityType::tryFrom((string) $get('entity_type'));
        if ($type === null) {
            return '—';
        }

        $key = $type->isSingleton() ? $type->singletonKey() : (string) $get('entity_key');

        return $type->path($key);
    }

    private static function serpPreview(Get $get): HtmlString
    {
        $site = rtrim((string) config('shared.frontend_url'), '/');
        $canonical = trim((string) $get('canonical')) ?: self::pathFor($get);
        $url = str_starts_with($canonical, 'http') ? $canonical : $site.$canonical;
        $title = trim((string) $get('meta_title.en')) ?: 'Untitled — a default title will be derived';
        $desc = trim((string) $get('meta_description.en')) ?: 'No meta description set — add one for a better snippet.';

        return new HtmlString(
            '<div style="font-family:Arial,sans-serif;max-width:600px;">'
            .'<div style="color:#1a0dab;font-size:18px;line-height:1.3;">'.e($title).'</div>'
            .'<div style="color:#006621;font-size:13px;">'.e($url).'</div>'
            .'<div style="color:#545454;font-size:13px;line-height:1.4;">'.e($desc).'</div>'
            .'</div>'
        );
    }

    private static function warningsPreview(Get $get): HtmlString
    {
        $warnings = [];
        if (trim((string) $get('meta_title.en')) === '') {
            $warnings[] = 'Missing meta title (EN) — a title will be derived from the entity.';
        }
        if (trim((string) $get('meta_description.en')) === '') {
            $warnings[] = 'Missing meta description (EN) — add one for a better search snippet.';
        }
        if (trim((string) $get('og_image')) === '') {
            $warnings[] = 'Missing social image (og:image) — shares will have no preview image.';
        }

        if ($warnings === []) {
            return new HtmlString('<span style="color:#15803d;">No warnings — this record looks complete.</span>');
        }

        $items = implode('', array_map(fn (string $w): string => '<li>'.e($w).'</li>', $warnings));

        return new HtmlString('<ul style="color:#b45309;margin:0;padding-inline-start:1.25rem;">'.$items.'</ul>');
    }

    private static function encodeJson(mixed $state): ?string
    {
        if (is_array($state)) {
            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
        }

        return is_string($state) && trim($state) !== '' ? $state : null;
    }

    /** @return array<mixed>|null */
    private static function decodeJson(mixed $state): ?array
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        $decoded = json_decode($state, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('entity_type')
            ->columns([
                TextColumn::make('entity_type')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => $state instanceof SeoEntityType ? $state->label() : $state),
                TextColumn::make('entity_key')->label('Key')->searchable()->wrap(),
                TextColumn::make('meta_title.en')->label('Title')->wrap()->placeholder('— (derived)')->toggleable(),
                TextColumn::make('canonical')->placeholder('— (entity URL)')->toggleable()->wrap(),
                IconColumn::make('robots_index')->boolean()->label('Index'),
                IconColumn::make('sitemap_enabled')->boolean()->label('Sitemap')->toggleable(),
                TextColumn::make('warnings')->label('Warnings')->badge()->color('warning')
                    ->state(fn (SeoMeta $record): int => count(app(SeoResolver::class)->warnings($record)))
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'OK' : (string) $state)
                    ->color(fn (int $state): string => $state === 0 ? 'success' : 'warning'),
                TextColumn::make('updated_at')->dateTime()->since()->label('Updated')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('entity_type')->options(SeoEntityType::options()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoMetas::route('/'),
            'create' => Pages\CreateSeoMeta::route('/create'),
            'edit' => Pages\EditSeoMeta::route('/{record}/edit'),
        ];
    }
}
