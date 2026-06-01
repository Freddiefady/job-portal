<?php

namespace App\Http\Resources;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Applicant data resolved from the applicant user and profile.
 *
 * @mixin JobApplication
 */
class ApplicantSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $applicant = $this->relationLoaded('applicant') ? $this->applicant : $this->applicant()->first();
        $profile = $applicant?->jobSeekerProfile;

        return [
            'name' => is_string($applicant?->full_name) ? trim($applicant->full_name) : null,
            'email' => $applicant?->email,
            'phone' => $applicant?->phone,
            'linkedin' => $profile?->linkedin_url,
            'cv_url' => $profile?->cvPublicUrl(),
        ];
    }
}
