<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Disability extends Model
{
    /**
     * @return BelongsToMany<JobPosting, $this>
     */
    public function jobPostings(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class, 'disability_job_posting')
            ->withTimestamps();
    }
}
