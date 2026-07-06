<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Models\Course;
use App\Shared\Actions\BaseAction;

class UpdateCourseAction extends BaseAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Course $course, array $data): Course
    {
        return $this->transaction(function () use ($course, $data): Course {
            $course->fill(array_filter([
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'language_id' => $data['language_id'] ?? null,
                'visibility' => $data['visibility'] ?? null,
                'thumbnail_path' => $data['thumbnail_path'] ?? null,
                'seo' => $data['seo'] ?? null,
            ], fn ($v) => $v !== null));
            $course->save();

            if (isset($data['category_ids'])) {
                $course->categories()->sync($data['category_ids']);
            }
            if (isset($data['tag_ids'])) {
                $course->tags()->sync($data['tag_ids']);
            }
            if (isset($data['trainer_ids'])) {
                $course->trainers()->sync($data['trainer_ids']);
            }

            return $course->fresh(['level', 'language', 'categories', 'tags', 'trainers']);
        });
    }
}
