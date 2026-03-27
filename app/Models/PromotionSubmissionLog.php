<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PromotionSubmissionLog extends Model
{
    protected $fillable = [
        'promotion_submission_id',
        'level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function promotionSubmission(): BelongsTo
    {
        return $this->belongsTo(PromotionSubmission::class);
    }
}
