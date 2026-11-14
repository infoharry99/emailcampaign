<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\MarketingContact;
use App\Models\MarketingEmail;
use App\Models\MarketingEmailEvent;
use App\Models\MarketingSetting;
use App\Services\AwsSesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingDashboardController extends Controller
{
    public function index(Request $request, AwsSesService $sesService)
    {
        $campaignId = $request->get('campaign_id');
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // High level counts
        $totalContacts = MarketingContact::count();
        $totalSubscribed = MarketingContact::where('subscription_status', 'subscribed')->where('consent_status', 'granted')->count();
        $totalCampaigns = MarketingCampaign::count();

        // Emails aggregation filtered by date / campaign
        $emailsQuery = MarketingEmail::query();
        if ($campaignId) {
            $emailsQuery->where('campaign_id', $campaignId);
        }
        if ($dateFrom) {
            $emailsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $emailsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $emailsGenerated = (clone $emailsQuery)->whereNotNull('generated_at')->count();
        $emailsQueued = (clone $emailsQuery)->whereIn('status', ['queued', 'sent', 'delivered'])->count();
        $emailsSent = (clone $emailsQuery)->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->count();
        $emailsDelivered = (clone $emailsQuery)->whereIn('status', ['delivered', 'opened', 'clicked'])->count();
        $emailsOpened = (clone $emailsQuery)->whereIn('status', ['opened', 'clicked'])->count();
        $emailsClicked = (clone $emailsQuery)->where('status', 'clicked')->count();
        $emailsBounced = (clone $emailsQuery)->where('status', 'bounced')->count();
        $emailsComplained = (clone $emailsQuery)->where('status', 'complained')->count();

        // Rates
        $openRate = $emailsDelivered > 0 ? round(($emailsOpened / $emailsDelivered) * 100, 1) : 0;
        $clickRate = $emailsDelivered > 0 ? round(($emailsClicked / $emailsDelivered) * 100, 1) : 0;
        $bounceRate = $emailsSent > 0 ? round(($emailsBounced / $emailsSent) * 100, 1) : 0;
        $complaintRate = $emailsSent > 0 ? round(($emailsComplained / $emailsSent) * 100, 2) : 0;

        // OpenAI usage and cost
        $openaiTokens = (int) MarketingSetting::get('openai_total_tokens_used', 0);
        $openaiCost = (float) MarketingSetting::get('openai_total_cost_usd', 0.0);

        // SES Statistics
        $sesStats = $sesService->getSendStatistics();

        // Recent Campaigns
        $campaigns = MarketingCampaign::latest()->get();
        $recentEmails = MarketingEmail::with(['campaign', 'contact'])->latest()->limit(10)->get();

        return view('marketing.dashboard', compact(
            'totalContacts',
            'totalSubscribed',
            'totalCampaigns',
            'emailsGenerated',
            'emailsQueued',
            'emailsSent',
            'emailsDelivered',
            'emailsOpened',
            'emailsClicked',
            'emailsBounced',
            'emailsComplained',
            'openRate',
            'clickRate',
            'bounceRate',
            'complaintRate',
            'openaiTokens',
            'openaiCost',
            'sesStats',
            'campaigns',
            'recentEmails',
            'campaignId',
            'dateFrom',
            'dateTo'
        ));
    }
}
