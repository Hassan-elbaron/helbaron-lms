<?php

namespace App\Contexts\Learning\Filament\Resources;

use App\Contexts\Learning\Enums\EnrollmentStatus;
use App\Contexts\Learning\Filament\Resources\EnrollmentResource\Pages;
use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Identity\Contracts\UserLookupPort;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Admin resource for learning enrollments (read + light state correction). Creation is disabled
 * so enrollments are only minted through the domain enrollment flow, not the panel.
 */
class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?string $recordRouteKeyName = 'public_id';

    /**
     * Learner names resolved for this request, keyed by user id.
     *
     * Request-scoped by construction: Filament resources are static, and a PHP process handles one
     * request at a time, so this is a per-render identity map rather than a cache with a lifetime.
     *
     * @var array<int, string>
     */
    private static array $learnerNames = [];

    /**
     * Eager-load the course for the table. `state()` reads $record->course to get the LOCALIZED
     * title, and without this that is one query per row — trading the learner N+1 for a course one.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('course');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all())
                ->required(),
            TextInput::make('progress_percentage')->numeric()->minValue(0)->maxValue(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->label('Learner')
                    // Resolved for the WHOLE PAGE in one call, not once per row. The per-row
                    // refById() this replaces cost two queries per row and re-ran on every sort,
                    // filter, search and page change.
                    ->formatStateUsing(fn ($state, $livewire): string => self::learnerName(
                        (int) $state,
                        $livewire,
                    ))
                    // Restores the capability lost when the column stopped rendering user.email:
                    // an admin looking up an enrollment types a learner's email or name. The port
                    // turns that into ids; this context never joins to the users table.
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $ids = app(UserLookupPort::class)->idsMatching($search);

                        // No match must return NOTHING, not everything. whereIn with an empty array
                        // is already false in SQL, but saying so explicitly keeps it obvious.
                        return $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('user_id', $ids);
                    }),
                TextColumn::make('course.title')
                    ->label('Course')
                    // The RAW scalar is blank for exactly the i18n-only courses this column was
                    // changed to support — it has to go through the same localized() accessor every
                    // other surface uses, or the cell an admin needs is the one that stays empty.
                    ->state(fn (Enrollment $record): string => self::courseTitle($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'course',
                        fn (Builder $courses): Builder => $courses
                            ->where('title', 'like', '%'.$search.'%')
                            ->orWhere('title_i18n', 'like', '%'.$search.'%'),
                    ))
                    // Plain ->sortable(), as before: Filament sorts a relation column by joining,
                    // which needs no reference to the Catalog model from this context. A custom
                    // sort subquery would have meant importing Course here — a new cross-context
                    // dependency for an ordering nicety, and the Deptrac baseline does not grow.
                    ->sortable(),
                TextColumn::make('course.public_id')->label('Course ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('progress_percentage')->label('Progress %')->numeric()->sortable(),
                TextColumn::make('source')->toggleable(),
                TextColumn::make('enrolled_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all(),
                ),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }

    /**
     * The learner's display name, resolved once per PAGE of results.
     *
     * The N+1 this replaces was not a slow-page annoyance: refById() is two queries (user + profile)
     * and Filament re-renders the whole table on every sort, filter, keystroke of a search and page
     * change, so a 50-row page cost 100 queries per interaction. The column it replaced
     * (`user.email`) had been eager-loaded by Filament automatically, so this was a regression
     * introduced with the port, not an old debt.
     *
     * Primed from the records Livewire already holds for this page — one call for up to fifty ids.
     * Falls back to a single lookup if the table component is not available (a different render
     * path, or a future Filament change), so the cell is never wrong, only occasionally slower.
     */
    /**
     * The course title in the viewer's locale.
     *
     * method_exists rather than a type hint on Course: importing the Catalog model here would be a
     * new cross-context dependency, and the Deptrac baseline does not grow. It also degrades
     * honestly — a course without the translation trait renders its scalar rather than throwing.
     */
    private static function courseTitle(Enrollment $record): string
    {
        $course = $record->course;

        if ($course === null) {
            return '';
        }

        return method_exists($course, 'localized')
            ? (string) $course->localized('title')
            : (string) ($course->getAttribute('title') ?? '');
    }

    private static function learnerName(int $userId, mixed $livewire = null): string
    {
        if (! array_key_exists($userId, self::$learnerNames)) {
            self::primeLearnerNames($userId, $livewire);
        }

        return self::$learnerNames[$userId] ?? 'Unknown learner';
    }

    /**
     * Resolve every user id on the current page in one port call.
     */
    private static function primeLearnerNames(int $userId, mixed $livewire): void
    {
        $ids = [$userId];

        if (is_object($livewire) && method_exists($livewire, 'getTableRecords')) {
            try {
                foreach ($livewire->getTableRecords() as $record) {
                    $ids[] = (int) $record->getAttribute('user_id');
                }
            } catch (Throwable) {
                // A page render must not fail because a batching optimisation could not read the
                // table's records; the single id above is enough to render this cell correctly.
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        foreach (app(UserLookupPort::class)->refsByIds($ids) as $id => $ref) {
            self::$learnerNames[(int) $id] = $ref->name;
        }

        // Ids with no user must be remembered too, or every row for a deleted learner re-queries.
        foreach ($ids as $id) {
            self::$learnerNames[$id] ??= 'Unknown learner';
        }
    }
}
