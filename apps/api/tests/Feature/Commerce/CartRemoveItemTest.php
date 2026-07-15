<?php

use App\Contexts\Commerce\Models\Product;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/CommerceHelpers.php';

/** A second published course + product (no contract template — courseProduct already seeds one). */
function extraProduct(int $amountMinor = 29900): Product
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->published()->create(['course_id' => $course->id]);
    Lesson::factory()->published()->create(['section_id' => $section->id]);

    $product = Product::factory()->create();
    $product->courses()->sync([$course->id]);
    $product->prices()->create(['currency' => 'SAR', 'amount_minor' => $amountMinor, 'is_default' => true]);

    return $product;
}

it('removes a single item from the cart without clearing the rest', function () {
    [, $productA] = courseProduct(19900);
    $productB = extraProduct(29900);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cart', ['product' => $productA->public_id])->assertOk();
    $this->postJson('/api/v1/cart', ['product' => $productB->public_id])->assertOk();

    $this->getJson('/api/v1/cart')->assertOk()->assertJsonCount(2, 'data.items');

    $this->deleteJson("/api/v1/cart/items/{$productA->public_id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.product_id', $productB->public_id);
});

it('returns 404 when removing a product that is not a real product', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/cart/items/00000000-0000-0000-0000-000000000000')->assertNotFound();
});
