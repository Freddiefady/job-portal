<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Test that uploading a CV does not delete the user's phone number.
     */
    public function test_uploading_cv_does_not_delete_phone_number(): void
    {
        Storage::fake('public');

        $user = User::factory()->jobSeeker()->create([
            'phone' => '+1234567890',
        ]);

        Sanctum::actingAs($user);

        // Upload CV file without passing the 'phone' input
        $response = $this->postJson('/api/profile', [
            'cv' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(200);

        $user->refresh();

        // Assert phone number is preserved!
        $this->assertEquals('+1234567890', $user->phone);

        // Assert CV is uploaded and saved!
        $this->assertNotNull($user->jobSeekerProfile->cv_path);
        Storage::disk('public')->assertExists($user->jobSeekerProfile->cv_path);
    }
}
