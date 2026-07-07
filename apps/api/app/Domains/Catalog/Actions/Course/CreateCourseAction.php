<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Services\SlugService;
use App\Platform\Shared\Actions\BaseAction;

class CreateCourseAction extends BaseAction
{
    public function __construct(private readonly SlugService $slugs) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Course
    {
        return $this->transaction(function () use ($data): Course {
            $course = Course::create([
                'title' => $data['title'],
                'slug' => $data['slug'] ?? $this->slugs->forModel(Course::class, $data['title']),
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'language_id' => $data['language_id'] ?? null,
                'status' => CourseStatus::Draft->value,
                'visibility' => $data['visibility'] ?? 'public',
                'seo' => $data['seo'] ?? null,
            ]);

            $this->syncRelations($course, $data);

            return $course;
        });
    }

    /** @param array<string, mixed> $data */
    private function syncRelations(Course $course, array $data): void
    {
        if (isset($data['category_ids'])) {
            $course->categories()->sync($data['category_ids']);
        }
        if (isset($data['tag_ids'])) {
            $course->tags()->sync($data['tag_ids']);
        }
        if (isset($data['trainer_ids'])) {
            $course->trainers()->sync($data['trainer_ids']);
        }
    }
}
