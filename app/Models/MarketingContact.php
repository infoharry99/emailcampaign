<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MarketingContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'company',
        'website',
        'phone',
        'business_type',
        'business_description',
        'verification_status',
        'consent_status',
        'consent_date',
        'consent_source',
        'consent_proof',
        'subscription_status',
        'unsubscribed_at',
        'unsubscribe_token',
        'emails_sent_count',
        'emails_opened_count',
        'emails_clicked_count',
        'last_emailed_at',
    ];

    protected $casts = [
        'consent_date' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_emailed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($contact) {
            if (empty($contact->unsubscribe_token)) {
                $contact->unsubscribe_token = Str::random(48);
            }
        });
    }

    public function emails()
    {
        return $this->hasMany(MarketingEmail::class, 'contact_id');
    }

    public function events()
    {
        return $this->hasMany(MarketingEmailEvent::class, 'contact_id');
    }

    public function consentLogs()
    {
        return $this->hasMany(MarketingConsentLog::class, 'contact_id');
    }

    public function lists()
    {
        return $this->belongsToMany(
            MarketingContactList::class,
            'marketing_contact_list_members',
            'contact_id',
            'contact_list_id'
        )->withTimestamps();
    }

    public function isSendable(): bool
    {
        if ($this->subscription_status !== 'subscribed') {
            return false;
        }

        if ($this->consent_status !== 'granted') {
            return false;
        }

        if (MarketingSuppression::isSuppressed($this->email)) {
            return false;
        }

        return true;
    }
}
