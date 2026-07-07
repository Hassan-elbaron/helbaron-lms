<?php

namespace App\Contexts\Commerce\Filament\Resources;

use App\Contexts\Commerce\Filament\Resources\OrderResource\Pages;
use App\Contexts\Commerce\Models\Order;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrder::route('/')];
    }
}
