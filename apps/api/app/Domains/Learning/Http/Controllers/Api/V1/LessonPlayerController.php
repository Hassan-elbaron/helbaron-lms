<?php

namespace App\Domains\Learning\Http\Controllers\Api\V1;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Learning\Http\Resources\LearnerLessonResource;
use App\Domains\Learning\Models\LessonBookmark;
use App\Domains\Learning\Models\LessonNote;
use App\Domains\Learning\Models\LessonProgress;
use App\Domains\Learning\Services\LearningMediaService;
use App\Domains\Learning\Services\LessonAccessService;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LessonPlayerController extends Controller
{
    public function show(Request $request, Lesson $lesson, LessonAccessService $access, LearningMediaService $media): JsonResponse
    {
        $user = $request->user();
        $enrollment = $access->assertAccess($user, $lesson); // throws 403 if locked/not enrolled

        $playback = $media->hasMedia($lesson) ? $media->playbackFor($user, $lesson) : null;

        $progress = LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)->first();

        $bookmarked = LessonBookmark::where('user_id', $user->id)->where('lesson_id', $lesson->id)->exists();
        $note = LessonNote::where('user_id', $user->id)->where('lesson_id', $lesson->id)->value('body');

        [$prev, $next] = $this->navigation($lesson);

        return ApiResponse::success(new LearnerLessonResource([
            'lesson' => $lesson,
            'playback' => $playback,
            'progress_status' => $progress?->status->value ?? 'not_started',
            'position_seconds' => $progress?->position_seconds,
            'bookmarked' => $bookmarked,
            'note' => $note,
            'prev' => $prev,
            'next' => $next,
        ]));
    }

    /** @return array{0: ?string, 1: ?string} previous/next lesson public_ids in curriculum order */
    private function navigation(Lesson $lesson): array
    {
        $courseId = Section::whereKey($lesson->section_id)->value('course_id');
        $sectionIds = Section::where('course_id', $courseId)->published()->orderBy('position')->pluck('id');

        $ordered = Lesson::whereIn('section_id', $sectionIds)
            ->published()
            ->orderBy('section_id')->orderBy('position')
            ->get(['id', 'public_id']);

        $index = $ordered->search(fn ($l) => $l->id === $lesson->id);

        return [
            $index > 0 ? $ordered[$index - 1]->public_id : null,
            $index !== false && $index < $ordered->count() - 1 ? $ordered[$index + 1]->public_id : null,
        ];
    }
}
