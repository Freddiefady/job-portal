<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'cv_path',
    'gender',
    'disability_type',
    'linkedin_url',
    'portfolio_url',
    'summary',
    'job_recommendation',
])]
class JobSeekerProfile extends Model
{
    /** @var string */
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_recommendation' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL when the CV is stored on the public disk (under /storage/...).
     */
    public function cvPublicUrl(): ?string
    {
        $path = $this->cv_path;
        if ($path === null || $path === '' || str_contains($path, '..')) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
