<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Http\Resources\LearnerLessonResource;
use App\Contexts\Learning\Models\LessonBookmark;
use App\Contexts\Learning\Models\LessonNote;
use App\Contexts\Learning\Models\LessonProgress;
use App\Contexts\Learning\Services\LearningMediaService;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Curriculum\Data\LessonRef;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LessonPlayerController extends Controller
{
    public function show(Request $request, string $lesson, LessonAccessService $access, LearningMediaService $media, CurriculumReadPort $curriculum): JsonResponse
    {
        $user = $request->user();

        $ref = $curriculum->findLessonByPublicId($lesson);
        if ($ref === null) {
            throw new NotFoundHttpException('Lesson not found.');
        }

        $enrollment = $access->assertAccessByUserId($user->id, $ref->id); // throws 403 if locked/not enrolled

        $playback = $media->hasMediaForLesson($ref->id) ? $media->playbackForLessonByUserId($user->id, $ref->id) : null;

        $progress = LessonProgress::where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $ref->id)->first();

        $bookmarked = LessonBookmark::where('user_id', $user->id)->where('lesson_id', $ref->id)->exists();
        $note = LessonNote::where('user_id', $user->id)->where('lesson_id', $ref->id)->value('body');

        [$prev, $next] = $this->navigation($curriculum, $ref);

        return ApiResponse::success(new LearnerLessonResource([
            'lesson' => $ref,
            'content' => $ref->content,
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
    private function navigation(CurriculumReadPort $curriculum, LessonRef $ref): array
    {
        $ordered = $curriculum->orderedPublishedLessonRefs($ref->courseId);

        $index = null;
        foreach ($ordered as $i => $item) {
            if ($item->id === $ref->id) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return [null, null];
        }

        return [
            $index > 0 ? $ordered[$index - 1]->publicId : null,
            $index < count($ordered) - 1 ? $ordered[$index + 1]->publicId : null,
        ];
    }
}
