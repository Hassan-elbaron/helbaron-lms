<?php

namespace App\Domains\Catalog\Actions\Category;

use App\Domains\Catalog\Events\CategoryCreated;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Services\SlugService;
use App\Shared\Actions\BaseAction;

class CreateCategoryAction extends BaseAction
{
    public function __construct(private readonly SlugService $slugs) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Category
    {
        $category = $this->transaction(function () use ($data): Category {
            return Category::create([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->slugs->forModel(Category::class, $data['name']),
                'description' => $data['description'] ?? null,
                'position' => $data['position'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'seo' => $data['seo'] ?? null,
            ]);
        });

        CategoryCreated::dispatch($category);

        return $category;
    }
}
