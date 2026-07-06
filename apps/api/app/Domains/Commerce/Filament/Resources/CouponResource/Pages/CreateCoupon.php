<?php

namespace App\Domains\Commerce\Filament\Resources\CouponResource\Pages;

use App\Domains\Commerce\Filament\Resources\CouponResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;
}
