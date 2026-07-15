<?php

namespace App\Contexts\Commerce\Filament\Resources;

use App\Contexts\Commerce\Actions\Payment\RefundOrderAction;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Filament\Resources\OrderResource\Pages;
use App\Contexts\Commerce\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|\UnitEnum|null $navigationGroup = 'Commerce';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('public_id')->label('Order')->searchable(),
            TextColumn::make('user.email')->label('User')->toggleable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('total_minor')->label('Total (minor)')->sortable(),
            TextColumn::make('currency'),
            TextColumn::make('paid_at')->dateTime()->toggleable(),
        ])->defaultSort('id', 'desc')
            ->recordActions([
                // Orchestration only: delegates to the domain RefundOrderAction (locking,
                // idempotency, gateway call, and audit all live in the action).
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Refund this paid order and revoke the related enrollment? This cannot be undone.')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid)
                    ->action(function (Order $record): void {
                        try {
                            app(RefundOrderAction::class)->execute($record);
                            Notification::make()->title('Order refunded')->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrder::route('/')];
    }
}
