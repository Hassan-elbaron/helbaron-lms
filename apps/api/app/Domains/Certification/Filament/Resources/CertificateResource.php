<?php

namespace App\Domains\Certification\Filament\Resources;

use App\Domains\Certification\Actions\ReissueCertificateAction;
use App\Domains\Certification\Actions\RevokeCertificateAction;
use App\Domains\Certification\Enums\CertificateStatus;
use App\Domains\Certification\Filament\Resources\CertificateResource\Pages;
use App\Domains\Certification\Models\Certificate;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Certification';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('number')->searchable(),
            TextColumn::make('user.email')->label('Holder')->toggleable(),
            TextColumn::make('course.title')->label('Course')->toggleable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('issued_at')->dateTime()->toggleable(),
        ])->defaultSort('id', 'desc')
            ->recordActions([
                // Orchestration only: delegate to domain actions (status transition + audit + events).
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => $record->status === CertificateStatus::Issued)
                    ->action(function (Certificate $record): void {
                        try {
                            app(RevokeCertificateAction::class)->execute($record);
                            Notification::make()->title('Certificate revoked')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('Revoke failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reissue')
                    ->label('Reissue')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => $record->status === CertificateStatus::Revoked)
                    ->action(function (Certificate $record): void {
                        try {
                            app(ReissueCertificateAction::class)->execute($record);
                            Notification::make()->title('Certificate reissued')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('Reissue failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCertificate::route('/')];
    }
}
