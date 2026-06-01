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
     * Get the cached AI recommendations from the database (instant load).
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->jobSeekerProfile;
        $recommendations = $profile?->job_recommendation ?? [];

        return ApiResponse::data($recommendations);
    }

    /**
     * Generate new recommendations using Gemini AI, save them, and return.
     */
    public function generate(Request $request, JobRecommendationService $service): JsonResponse
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

        $profile = $user->jobSeekerProfile()->firstOrCreate(['user_id' => $user->id]);
        $profile->job_recommendation = $formatted;
        $profile->save();

        return ApiResponse::data($formatted, 201);
    }
}
