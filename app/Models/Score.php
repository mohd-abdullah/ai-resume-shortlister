<?php

// app/Models/Score.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Resume $resume
 */
class Score extends Model
{
    use HasFactory;

    protected $fillable = ['resume_id', 'score', 'matched_keywords','summary'];

    protected $casts = [
        'matched_keywords' => 'array',
    ];

    /**
     * @return BelongsTo<Resume, Score>
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
