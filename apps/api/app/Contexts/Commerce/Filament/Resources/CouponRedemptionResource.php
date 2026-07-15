<?php

namespace App\Contexts\Commerce\Filament\Resources;

use App\Contexts\Commerce\Filament\Resources\CouponRedemptionResource\Pages;
use App\Contexts\Commerce\Models\CouponRedemption;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only visibility into coupon redemptions (abuse monitoring / support). Redemptions are
 * written only by the checkout flow — never created or edited from the admin.
 */
class CouponRedemptionResource extends Resource
{
    protected static ?string $model = CouponRedemption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'Commerce';

    protected static ?string $navigationLabel = 'Coupon Redemptions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('coupon.code')->label('Coupon')->searchable()->sortable(),
            TextColumn::make('user_id')->label('User')->toggleable(),
            TextColumn::make('order_id')->label('Order')->toggleable(),
            TextColumn::make('created_at')->dateTime()->label('Redeemed at')->sortable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCouponRedemption::route('/')];
    }
}
