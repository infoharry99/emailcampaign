<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingEmailEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'marketing_email_id',
        'ses_message_id',
        'recipient_email',
        'event_type',
        'event_timestamp',
        'ip_address',
        'user_agent',
        'clicked_url',
        'payload',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'payload' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function contact()
    {
        return $this->belongsTo(MarketingContact::class, 'contact_id');
    }

    public function email()
    {
        return $this->belongsTo(MarketingEmail::class, 'marketing_email_id');
    }
}
