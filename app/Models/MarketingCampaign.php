<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'purpose',
        'product_service_details',
        'target_criteria',
        'subject',
        'content',
        'attachments',
        'sending_date',
        'sending_time',
        'scheduled_at',
        'sending_mode',
        'status',
        'total_recipients',
        'generated_count',
        'queued_count',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'complained_count',
        'failed_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'sending_date' => 'date',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function emails()
    {
        return $this->hasMany(MarketingEmail::class, 'campaign_id');
    }

    public function events()
    {
        return $this->hasMany(MarketingEmailEvent::class, 'campaign_id');
    }

    public function calculateTargetRecipientsCount(): int
    {
        $query = MarketingContact::query()
            ->where('subscription_status', 'subscribed')
            ->where('consent_status', 'granted')
            ->whereNotIn('email', function ($q) {
                $q->select('email')->from('marketing_suppressions');
            });

        if (!empty($this->target_criteria) && $this->target_criteria !== 'all') {
            if (str_starts_with($this->target_criteria, 'list:')) {
                $listId = (int) substr($this->target_criteria, 5);
                $query->whereHas('lists', function ($q) use ($listId) {
                    $q->where('marketing_contact_lists.id', $listId);
                });
            } elseif (str_starts_with($this->target_criteria, 'business_type:')) {
                $type = substr($this->target_criteria, 14);
                $query->where('business_type', $type);
            }
        }

        return $query->count();
    }

    public function recalculateStats(): void
    {
        $targetCount = $this->calculateTargetRecipientsCount();
        $realEmails = $this->emails()->where(function ($q) {
            $q->whereNull('subject')->orWhere('subject', 'not like', '[TEST]%');
        });

        $realCount = (clone $realEmails)->count();
        $totalRecipients = $realCount > 0 ? max($realCount, $targetCount) : $targetCount;

        $this->update([
            'total_recipients' => $totalRecipients,
            'generated_count' => (clone $realEmails)->whereNotNull('generated_at')->count(),
            'queued_count' => (clone $realEmails)->whereIn('status', ['queued', 'sent', 'delivered'])->count(),
            'sent_count' => (clone $realEmails)->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->count(),
            'delivered_count' => (clone $realEmails)->whereIn('status', ['delivered', 'opened', 'clicked'])->count(),
            'opened_count' => (clone $realEmails)->whereIn('status', ['opened', 'clicked'])->count(),
            'clicked_count' => (clone $realEmails)->where('status', 'clicked')->count(),
            'bounced_count' => (clone $realEmails)->where('status', 'bounced')->count(),
            'complained_count' => (clone $realEmails)->where('status', 'complained')->count(),
            'failed_count' => (clone $realEmails)->where('status', 'failed')->count(),
        ]);
    }
}
