<?php

namespace App\Domains\Certification\Filament\Resources;

use App\Domains\Certification\Filament\Resources\CertificateSettingResource\Pages;
use App\Domains\Certification\Models\CertificateSetting;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Admin management for certificate issuer/signature settings (the certificate branding record).
 * Presentation only — standard Eloquent persistence on the CertificateSetting model.
 */
class CertificateSettingResource extends Resource
{
    protected static ?string $model = CertificateSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Certification';

    protected static ?string $navigationLabel = 'Certificate Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('issuer_name')->required()->maxLength(255)
                ->helperText('Organization name printed as the certificate issuer.'),
            TextInput::make('signature_name')->maxLength(255),
            TextInput::make('signature_title')->maxLength(255),
            TextInput::make('signature_image_path')->maxLength(255)
                ->helperText('Storage path of the signature image (uploaded via media pipeline).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('issuer_name'),
            TextColumn::make('signature_name')->toggleable(),
            TextColumn::make('signature_title')->toggleable(),
            TextColumn::make('updated_at')->dateTime()->toggleable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateSetting::route('/'),
            'edit' => Pages\EditCertificateSetting::route('/{record}/edit'),
        ];
    }
}
