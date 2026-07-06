<?php

namespace App\Domains\Commerce\Database\Seeders;

use App\Domains\Catalog\Models\Course;
use App\Domains\Commerce\Enums\CommercePermission;
use App\Domains\Commerce\Enums\ProductStatus;
use App\Domains\Commerce\Enums\ProductType;
use App\Domains\Commerce\Models\ContractTemplate;
use App\Domains\Commerce\Models\Product;
use App\Domains\Identity\Enums\Role;
use App\Shared\Helpers\Slug;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds commerce permissions, the active terms contract, and a product per published course.
 * Idempotent.
 */
class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (CommercePermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        SpatieRole::findByName(Role::Admin->value, 'web')->givePermissionTo(CommercePermission::values());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ContractTemplate::firstOrCreate(
            ['key' => 'terms', 'version' => 1],
            ['title' => 'Terms & Conditions', 'body' => 'By enrolling you accept the HElbaron terms.', 'is_active' => true],
        );

        Course::query()->where('status', 'published')->orderBy('id')->limit(3)->get()->each(function (Course $course): void {
            $product = Product::firstOrCreate(
                ['slug' => Slug::make($course->title).'-product'],
                ['type' => ProductType::Course->value, 'title' => $course->title, 'status' => ProductStatus::Active->value],
            );
            $product->courses()->syncWithoutDetaching([$course->id]);
            if ($product->prices()->doesntExist()) {
                $product->prices()->create(['currency' => 'SAR', 'amount_minor' => 19900, 'is_default' => true]);
            }
        });
    }
}
