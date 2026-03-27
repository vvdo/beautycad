<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PromotionSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'promotion_url',
        'status',
        'missing_fields',
        'result_message',
        'metadata',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'missing_fields' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PromotionSubmissionLog::class);
    }
}
