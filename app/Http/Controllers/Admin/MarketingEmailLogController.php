<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingEmail;
use App\Models\MarketingEmailEvent;
use Illuminate\Http\Request;

class MarketingEmailLogController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $campaignId = $request->get('campaign_id');
        $search = $request->get('search');

        $query = MarketingEmail::with(['campaign', 'contact']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('ses_message_id', 'like', "%{$search}%");
            });
        }

        $emails = $query->latest()->paginate(25)->withQueryString();
        $recentEvents = MarketingEmailEvent::latest()->limit(15)->get();

        return view('marketing.logs.index', compact('emails', 'recentEvents', 'status', 'campaignId', 'search'));
    }

    public function show(MarketingEmail $email)
    {
        $email->load(['campaign', 'contact', 'events']);
        return view('marketing.logs.show', compact('email'));
    }
}
