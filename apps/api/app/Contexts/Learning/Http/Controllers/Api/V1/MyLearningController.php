<?php

namespace App\Contexts\Learning\Http\Controllers\Api\V1;

use App\Contexts\Learning\Http\Resources\MyLearningItemResource;
use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MyLearningController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->with('course')
            ->latest('updated_at')
            ->get();

        return ApiResponse::success(MyLearningItemResource::collection($enrollments));
    }
}
