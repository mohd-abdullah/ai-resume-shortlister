<?php

// app/Models/Resume.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read \App\Models\Job $job
 * @property-read \App\Models\Score|null $score
 */
class Resume extends Model
{
    use HasFactory;

    protected $fillable = ['job_id', 'candidate_name', 'file_path', 'extracted_text'];

    /**
     * @return BelongsTo<Job, Resume>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return HasOne<Score>
     */
    public function score(): HasOne
    {
        return $this->hasOne(Score::class);
    }
}
