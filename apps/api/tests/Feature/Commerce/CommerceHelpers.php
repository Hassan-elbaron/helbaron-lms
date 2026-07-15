<?php

use App\Contexts\Commerce\Models\ContractTemplate;
use App\Contexts\Commerce\Models\Product;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;

/** A published course + a product that grants it, priced in SAR, plus an active terms template. */
function courseProduct(int $amountMinor = 19900): array
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->published()->create(['course_id' => $course->id]);
    Lesson::factory()->published()->create(['section_id' => $section->id]);

    $product = Product::factory()->create();
    $product->courses()->sync([$course->id]);
    $product->prices()->create(['currency' => 'SAR', 'amount_minor' => $amountMinor, 'is_default' => true]);

    ContractTemplate::factory()->create(['is_active' => true]);

    return [$course, $product];
}
