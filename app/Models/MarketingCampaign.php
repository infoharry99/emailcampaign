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
            if (str_starts_with($this->target_criteria, 'business_type:')) {
                $type = substr($this->target_criteria, 14);
                $query->where('business_type', $type);
            }
        }

        return $query->count();
    }

    public function recalculateStats(): void
    {
        $emailCount = $this->emails()->count();
        $targetCount = $this->calculateTargetRecipientsCount();

        $this->update([
            'total_recipients' => $emailCount > 0 ? $emailCount : $targetCount,
            'generated_count' => $this->emails()->whereNotNull('generated_at')->count(),
            'queued_count' => $this->emails()->whereIn('status', ['queued', 'sent', 'delivered'])->count(),
            'sent_count' => $this->emails()->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->count(),
            'delivered_count' => $this->emails()->whereIn('status', ['delivered', 'opened', 'clicked'])->count(),
            'opened_count' => $this->emails()->whereIn('status', ['opened', 'clicked'])->count(),
            'clicked_count' => $this->emails()->where('status', 'clicked')->count(),
            'bounced_count' => $this->emails()->where('status', 'bounced')->count(),
            'complained_count' => $this->emails()->where('status', 'complained')->count(),
            'failed_count' => $this->emails()->where('status', 'failed')->count(),
        ]);
    }
}
