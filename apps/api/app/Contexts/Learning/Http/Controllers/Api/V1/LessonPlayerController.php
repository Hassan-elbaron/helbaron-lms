<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Http\Resources\LearnerLessonResource;
use App\Contexts\Learning\Models\LessonBookmark;
use App\Contexts\Learning\Models\LessonNote;
use App\Contexts\Learning\Models\LessonProgress;
use App\Contexts\Learning\Services\LearningMediaService;
use App\Contexts\Learning\Services\LessonAccessService;
use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use App\Platform\Shared\Curriculum\Contracts\CurriculumReadPort;
use App\Platform\Shared\Curriculum\Data\LessonRef;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LessonPlayerController extends Controller
{
    public function show(Request $request, string $lesson, LessonAccessService $access, LearningMediaService $media, CurriculumReadPort $curriculum, LessonAssessmentPort $assessments): JsonResponse
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
            'assessment' => $this->learnerAssessment($ref, $assessments),
            'playback' => $playback,
            'progress_status' => $progress?->status->value ?? 'not_started',
            'position_seconds' => $progress?->position_seconds,
            'bookmarked' => $bookmarked,
            'note' => $note,
            'prev' => $prev,
            'next' => $next,
        ]));
    }

    /**
     * The learner-safe assessment reference for a quiz lesson, or null.
     *
     * PUBLISH-GATED: a draft or archived assessment is reported as no assessment at all. An author
     * mid-edit must not have their unfinished quiz appear to learners, and a learner who could see
     * a draft's id could start an attempt against it — the attempt endpoint would refuse, but the
     * id should never leave the server in the first place.
     *
     * Resolved through the existing LessonAssessmentPort rather than a new read contract, so
     * Learning still never imports an Assessment class.
     *
     * @return array{id: string, title: string, question_count: int, version: int}|null
     */
    private function learnerAssessment(LessonRef $ref, LessonAssessmentPort $assessments): ?array
    {
        if ($ref->assessmentId === null) {
            return null;
        }

        $assessment = $assessments->describe($ref->assessmentId);

        if ($assessment === null || ! $assessment->isPublished()) {
            return null;
        }

        // Deliberately NOT spread from the DTO: `status` stays server-side, and listing the four
        // exposed fields explicitly means a future field added to AssessmentRef cannot leak here
        // by accident.
        return [
            'id' => $assessment->publicId,
            'title' => $assessment->title,
            'question_count' => $assessment->questionCount,
            'version' => $assessment->version,
        ];
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
