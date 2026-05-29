<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\JobRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobRecommendationController extends Controller
{
    /**
     * Get AI-driven job recommendations for the authenticated job seeker.
     */
    public function index(Request $request, JobRecommendationService $service): JsonResponse
    {
        $user = $request->user();

        $recommendations = $service->getRecommendationsForUser($user);

        $formatted = array_map(function ($item): array {
            $posting = $item['job_posting'];

            return [
                'job_posting_id' => $posting->id,
                'title' => $posting->title,
                'location' => $posting->location,
                'type' => $posting->type?->value ?? $posting->type,
                'category' => $posting->category,
                'matching_reason' => $item['matching_reason'],
                'match_percentage' => $item['match_percentage'],
            ];
        }, $recommendations);

        return ApiResponse::data($formatted);
    }
}
