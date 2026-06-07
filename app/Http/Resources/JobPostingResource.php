<?php

namespace App\Http\Resources;

use App\Models\JobPosting;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobPosting
 */
class JobPostingResource extends JsonResource
{
    public function __construct($resource, private bool $includeOwnerId = true)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $body = [
            'id' => $this->id,
            'title' => $this->title,
            'company_name' => $this->user?->companyProfile?->company_name,
            'company_profile_photo_url' => $this->user?->profilePhotoPublicUrl(),
            'company_industry' => $this->user?->companyProfile?->industry,
            'company_size' => $this->user?->companyProfile?->company_size,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'qualification' => $this->qualification,
            'location' => $this->location,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'approved_disability' => $this->disabilities ? $this->disabilities->pluck('name')->all() : [],
            'category' => $this->category,
            'skills' => $this->skills ? $this->skills->pluck('name')->all() : [],
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
        ];

        if ($this->includeOwnerId) {
            return [
                'user_id' => $this->user_id,
                ...$body,
            ];
        }

        return [
            ...$body,
        ];
    }

    private function formatDateTime(?CarbonInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value
            ->copy()
            ->timezone((string) config('app.timezone', 'Africa/Cairo'))
            ->format('M j, Y \a\t g:i A');
    }
}
