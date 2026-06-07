<?php

namespace App\Services;

use App\Ai\Agents\JobRecommender;
use App\Models\JobPosting;
use App\Models\User;

class JobRecommendationService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected JobRecommender $recommender) {}

    /**
     * Get semantically matching job recommendations for the user.
     *
     * @return array<array{job_posting: JobPosting, matching_reason: string, match_percentage: int}>
     */
    public function getRecommendationsForUser(User $user): array
    {
        // 1. Load user profile details
        $user->loadMissing([
            'jobSeekerProfile:user_id,summary,linkedin_url,portfolio_url',
            'skills:id,name',
            'educations:id,user_id,institution,degree,field_of_study,starts_at,ends_at',
            'experiences:id,user_id,company_name,title,starts_at,ends_at,description',
            'certificates:id,user_id,name,issuer,issued_at',
        ]);

        // 2. Fetch visible job postings from database
        $jobs = JobPosting::visibleToPublic()
            ->with('skills')
            ->get([
                'id',
                'title',
                'description',
                'requirements',
                'qualification',
                'location',
                'type',
                'category',
            ]);

        if ($jobs->isEmpty()) {
            return [];
        }

        // 3. Format candidate profile details
        $profile = $user->jobSeekerProfile;
        $skills = $user->skills->pluck('name')->implode(', ');

        $educations = $user->educations->map(function ($edu): string {
            return "- {$edu->degree} in {$edu->field_of_study} from {$edu->institution}";
        })->implode("\n");

        $experiences = $user->experiences->map(function ($exp): string {
            return "- {$exp->title} at {$exp->company_name}\n  Description: {$exp->description}";
        })->implode("\n");

        $certificates = $user->certificates->map(function ($cert): string {
            return "- {$cert->name} issued by {$cert->issuer}";
        })->implode("\n");

        $candidateInfo = <<<TEXT
Candidate Profile:
- Name: {$user->first_name} {$user->last_name}
- Location: {$user->street}, {$user->city}
- Professional Summary: {$profile?->summary}
- Skills: {$skills}

Education:
{$educations}

Work Experience:
{$experiences}

Certificates:
{$certificates}
TEXT;

        // 4. Format job options
        $jobsList = $jobs->map(function ($job): array {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->category,
                'description' => $job->description,
                'requirements' => $job->requirements,
                'qualification' => $job->qualification,
                'skills' => $job->skills->pluck('name')->implode(', '),
            ];
        })->toArray();

        $jobsJson = json_encode($jobsList, JSON_PRETTY_PRINT);

        $prompt = <<<TEXT
You are provided with a candidate's profile and a list of available job postings.
Your job is to compare the candidate's profile details with the job requirements and select the top 5 best matching jobs.

Candidate Profile:
{$candidateInfo}

Available Job Postings:
{$jobsJson}

Semantically compare the candidate's skills, experience, education, and certs against each job.
Return the structured output with recommendations, matching reason, and match percentage for each selected job.
TEXT;

        // 5. Query the structured AI Agent
        $response = $this->recommender->prompt($prompt);

        $recommendations = $response['recommendations'] ?? [];

        // 6. Map the recommendations back to database models
        $results = [];
        foreach ($recommendations as $rec) {
            $jobPostingId = $rec['job_posting_id'] ?? null;
            if ($jobPostingId === null) {
                continue;
            }

            $jobPosting = $jobs->firstWhere('id', $jobPostingId);
            if ($jobPosting === null) {
                continue;
            }

            $results[] = [
                'job_posting' => $jobPosting,
                'matching_reason' => $rec['matching_reason'] ?? '',
                'match_percentage' => (int) ($rec['match_percentage'] ?? 0),
            ];
        }

        // Sort results by match percentage descending
        usort($results, function ($a, $b): int {
            return $b['match_percentage'] <=> $a['match_percentage'];
        });

        return $results;
    }
}
