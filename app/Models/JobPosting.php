<?php

namespace App\Models;

use App\Enums\JobPostingStatus;
use App\Enums\JobWorkType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'status',
    'title',
    'description',
    'requirements',
    'qualification',
    'location',
    'type',
    'category',
])]
class JobPosting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobPostingStatus::class,
            'type' => JobWorkType::class,
        ];
    }

    /**
     * @param  Builder<JobPosting>  $query
     * @return Builder<JobPosting>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', JobPostingStatus::Active->value);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * @return BelongsToMany<Disability, $this>
     */
    public function disabilities(): BelongsToMany
    {
        return $this->belongsToMany(Disability::class, 'disability_job_posting')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skills', 'job_posting_id', 'skill_id')
            ->withTimestamps();
    }

    /**
     * @param  array<int, string>  $names
     */
    public function syncSkills(array $names): void
    {
        $sync = [];
        $seen = [];

        foreach ($names as $rawName) {
            if (! is_string($rawName)) {
                continue;
            }
            $trimmed = mb_substr(trim($rawName), 0, 100);
            if ($trimmed === '') {
                continue;
            }
            $key = mb_strtolower($trimmed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $skill = Skill::query()->firstOrCreate(['name' => $trimmed]);
            $sync[] = $skill->id;
        }

        $this->skills()->sync($sync);
    }

    /**
     * @param  array<int, string>  $names
     */
    public function syncDisabilities(array $names): void
    {
        $sync = [];
        $seen = [];

        foreach ($names as $rawName) {
            if (! is_string($rawName)) {
                continue;
            }
            $trimmed = mb_substr(trim($rawName), 0, 100);
            if ($trimmed === '') {
                continue;
            }
            $key = mb_strtolower($trimmed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $disability = Disability::query()->firstOrCreate(['name' => $trimmed]);
            $sync[] = $disability->id;
        }

        $this->disabilities()->sync($sync);
    }

    /**
     * Job postings whose owning company account is active (public directory).
     *
     * @param  Builder<JobPosting>  $query
     * @return Builder<JobPosting>
     */
    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereHas('user', static function (Builder $userQuery): void {
                $userQuery->where('status', 'active');
            });
    }
}
