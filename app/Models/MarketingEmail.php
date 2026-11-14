<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_html',
        'body_text',
        'generation_mode',
        'is_ai_generated',
        'ai_prompt',
        'ai_metadata',
        'ses_message_id',
        'status',
        'error_message',
        'retry_count',
        'generated_at',
        'queued_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'complained_at',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
        'ai_metadata' => 'array',
        'generated_at' => 'datetime',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function contact()
    {
        return $this->belongsTo(MarketingContact::class, 'contact_id');
    }

    public function events()
    {
        return $this->hasMany(MarketingEmailEvent::class, 'marketing_email_id');
    }
}
