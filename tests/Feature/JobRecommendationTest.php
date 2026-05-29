<?php

namespace Tests\Feature;

use App\Ai\Agents\JobRecommender;
use App\Enums\JobWorkType;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobRecommendationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Test that guests cannot fetch recommendations.
     */
    public function test_job_recommendations_require_authentication(): void
    {
        $response = $this->getJson('/api/recommendations/jobs');

        $response->assertStatus(401);
    }

    /**
     * Test that companies cannot fetch recommendations.
     */
    public function test_companies_cannot_fetch_recommendations(): void
    {
        $user = User::factory()->company()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/recommendations/jobs');

        $response->assertStatus(403);
    }

    /**
     * Test that recommendations are returned successfully.
     */
    public function test_job_seeker_can_fetch_job_recommendations_successfully(): void
    {
        $user = User::factory()->jobSeeker()->create();

        if ($user->jobSeekerProfile) {
            $user->jobSeekerProfile->summary = 'Experienced engineer.';
            $user->jobSeekerProfile->save();
        }

        // Add complete seeker details
        $user->educations()->create([
            'institution' => 'University of Cairo',
            'degree' => 'B.Sc.',
            'field_of_study' => 'Computer Science',
            'starts_at' => '2020-09-01',
            'ends_at' => '2024-06-30',
        ]);

        $user->experiences()->create([
            'company_name' => 'Tech Solutions Ltd.',
            'title' => 'Software Engineer Intern',
            'starts_at' => '2023-07-01',
            'ends_at' => '2023-09-30',
            'description' => 'Developed full stack features using Laravel.',
        ]);

        // Create some visible job postings
        $company = User::factory()->company()->create();
        $job1 = JobPosting::create([
            'user_id' => $company->id,
            'title' => 'Laravel Backend Developer',
            'description' => 'Looking for an expert Laravel developer.',
            'requirements' => 'Laravel, PHP, MySQL',
            'qualification' => 'Bachelor\'s Degree',
            'location' => 'Cairo',
            'type' => JobWorkType::Fulltime,
            'category' => 'Engineering',
            'skills' => ['Laravel', 'PHP', 'MySQL'],
        ]);

        $job2 = JobPosting::create([
            'user_id' => $company->id,
            'title' => 'Angular Frontend Developer',
            'description' => 'Looking for an expert Angular developer.',
            'requirements' => 'Angular, TypeScript',
            'qualification' => 'Bachelor\'s Degree',
            'location' => 'Cairo',
            'type' => JobWorkType::Fulltime,
            'category' => 'Engineering',
            'skills' => ['Angular', 'TypeScript'],
        ]);

        Sanctum::actingAs($user);

        // Mock the JobRecommender structured output
        JobRecommender::fake([
            [
                'recommendations' => [
                    [
                        'job_posting_id' => $job1->id,
                        'matching_reason' => 'Strong background in PHP and Laravel.',
                        'match_percentage' => 95,
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/api/recommendations/jobs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'job_posting_id',
                    'title',
                    'location',
                    'type',
                    'category',
                    'matching_reason',
                    'match_percentage',
                ],
            ],
        ]);

        $response->assertJsonFragment([
            'job_posting_id' => $job1->id,
            'title' => 'Laravel Backend Developer',
            'matching_reason' => 'Strong background in PHP and Laravel.',
            'match_percentage' => 95,
        ]);

        JobRecommender::assertPrompted(fn ($prompt) => $prompt->contains('Laravel Backend Developer'));
    }
}
