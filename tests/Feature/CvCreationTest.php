<?php

namespace Tests\Feature;

use App\Ai\Agents\CvGenerator;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CvCreationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Test that guests cannot generate a CV.
     */
    public function test_cv_generation_requires_authentication(): void
    {
        $response = $this->postJson('/api/profile/cv');

        $response->assertStatus(401);
    }

    /**
     * Test that non-job seekers (e.g. companies) cannot generate a CV.
     */
    public function test_company_user_cannot_generate_cv(): void
    {
        $user = User::factory()->company()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/cv');

        $response->assertStatus(403);
    }

    /**
     * Test that an authenticated job seeker can generate a CV.
     */
    public function test_job_seeker_can_generate_cv_successfully(): void
    {
        $user = User::factory()->jobSeeker()->create();

        $user->jobSeekerProfile()->updateOrCreate([], [
            'summary' => 'Experienced software developer.',
        ]);

        // Create education, experience, and certificate records
        $user->educations()->create([
            'institution' => 'University of Cairo',
            'degree' => 'B.Sc.',
            'field_of_study' => 'Computer Science',
            'starts_at' => '2020-09-01',
            'ends_at' => '2024-06-30',
            'details' => 'Graduated with honors',
        ]);

        $user->experiences()->create([
            'company_name' => 'Tech Solutions Ltd.',
            'title' => 'Software Engineer Intern',
            'starts_at' => '2023-07-01',
            'ends_at' => '2023-09-30',
            'description' => 'Developed full stack features using Laravel.',
        ]);

        $user->certificates()->create([
            'name' => 'Laravel Developer Certification',
            'issuer' => 'Laravel LLC',
            'issued_at' => '2025-01-15',
            'credential_url' => 'https://certification.laravel.com/verify/12345',
        ]);

        $user->refresh();

        Sanctum::actingAs($user);

        // Fake the AI SDK agent
        CvGenerator::fake(['### Simulated AI CV Content']);

        $response = $this->postJson('/api/profile/cv');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'cv' => '### Simulated AI CV Content',
            ],
        ]);

        // Verify the agent was prompted with specific details from the user's profile
        CvGenerator::assertPrompted(fn ($prompt) => $prompt->contains('University of Cairo'));
        CvGenerator::assertPrompted(fn ($prompt) => $prompt->contains('Tech Solutions Ltd.'));
        CvGenerator::assertPrompted(fn ($prompt) => $prompt->contains('Laravel Developer Certification'));
    }

    /**
     * Test that CV generation requires a complete profile.
     */
    public function test_cv_generation_requires_complete_profile(): void
    {
        $user = User::factory()->jobSeeker()->create();
        $user->skills()->delete();
        $user->educations()->delete();
        $user->experiences()->delete();
        if ($user->jobSeekerProfile) {
            $user->jobSeekerProfile->summary = '';
            $user->jobSeekerProfile->save();
        }

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/cv');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profile']);
    }

    /**
     * Test that CV PDF download requires a complete profile.
     */
    public function test_cv_pdf_download_requires_complete_profile(): void
    {
        $user = User::factory()->jobSeeker()->create();
        $user->skills()->delete();
        $user->educations()->delete();
        $user->experiences()->delete();
        if ($user->jobSeekerProfile) {
            $user->jobSeekerProfile->summary = '';
            $user->jobSeekerProfile->save();
        }

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/cv/download');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profile']);
    }

    /**
     * Test that CV PDF download works successfully.
     */
    public function test_cv_pdf_download_works_successfully(): void
    {
        $user = User::factory()->jobSeeker()->create();

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
            'description' => 'Developed Laravel apps.',
        ]);

        $user->jobSeekerProfile()->updateOrCreate([], [
            'summary' => 'Passionate developer.',
        ]);

        $user->refresh();

        Sanctum::actingAs($user);

        CvGenerator::fake(['### Simulated AI CV Content']);

        $response = $this->get('/api/profile/cv/download');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="cv.pdf"');
        $this->assertNotEmpty($response->getContent());
    }
}
