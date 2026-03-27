<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'accept_terms',
        'allow_marketing_emails',
        'allow_marketing_sms',
        'allow_third_party_share',
        'receive_newsletter',
        'preferred_contact_channel',
        'auto_reject_cookies',
        'pause_on_captcha',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'accept_terms' => 'boolean',
            'allow_marketing_emails' => 'boolean',
            'allow_marketing_sms' => 'boolean',
            'allow_third_party_share' => 'boolean',
            'receive_newsletter' => 'boolean',
            'auto_reject_cookies' => 'boolean',
            'pause_on_captcha' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
