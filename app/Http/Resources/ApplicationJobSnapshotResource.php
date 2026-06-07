<?php

namespace App\Http\Resources;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Job fields stored on the application at submit time.
 *
 * @mixin JobApplication
 */
class ApplicationJobSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $posting = $this->jobPosting;

        return [
            'title' => $posting?->title,
            'company_name' => $this->resolveCompanyName(),
            'company_profile_photo_url' => $this->resolveCompanyProfilePhotoUrl(),
            'company_industry' => $this->resolveCompanyIndustry(),
            'company_size' => $this->resolveCompanySize(),
            'description' => $posting?->description,
            'requirements' => $posting?->requirements,
            'qualification' => $posting?->qualification,
            'location' => $posting?->location,
            'type' => $posting?->type?->value,
            'approved_disability' => $posting && $posting->disabilities ? $posting->disabilities->pluck('name')->all() : [],
            'category' => $posting?->category,
            'skills' => $posting && $posting->skills ? $posting->skills->pluck('name')->all() : [],
        ];
    }

    private function resolveCompanyName(): ?string
    {
        return $this->resolveCompanyOwner()?->companyProfile?->company_name;
    }

    private function resolveCompanyProfilePhotoUrl(): ?string
    {
        return $this->resolveCompanyOwner()?->profilePhotoPublicUrl();
    }

    private function resolveCompanyIndustry(): ?string
    {
        return $this->resolveCompanyOwner()?->companyProfile?->industry;
    }

    private function resolveCompanySize(): ?string
    {
        return $this->resolveCompanyOwner()?->companyProfile?->company_size;
    }

    private function resolveCompanyOwner(): ?User
    {
        if (! $this->relationLoaded('jobPosting')) {
            return null;
        }

        $posting = $this->jobPosting;
        if ($posting === null) {
            return null;
        }

        if ($posting->relationLoaded('user')) {
            return $posting->user;
        }

        return User::query()
            ->whereKey($posting->user_id)
            ->with('companyProfile')
            ->first();
    }
}
