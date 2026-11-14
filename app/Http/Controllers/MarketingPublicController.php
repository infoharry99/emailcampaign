<?php

namespace App\Http\Controllers;

use App\Models\MarketingContact;
use App\Models\MarketingConsentLog;
use App\Models\MarketingSuppression;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MarketingPublicController extends Controller
{
    /**
     * View unsubscribe confirmation page (GET)
     */
    public function showUnsubscribe(string $token)
    {
        $contact = MarketingContact::where('unsubscribe_token', $token)->first();

        return view('marketing.public.unsubscribe', [
            'contact' => $contact,
            'token' => $token,
            'isUnsubscribed' => $contact ? $contact->subscription_status === 'unsubscribed' : false,
        ]);
    }

    /**
     * Process Unsubscribe (POST - web form and RFC 8058 One-Click List-Unsubscribe-Post)
     */
    public function processUnsubscribe(Request $request, string $token)
    {
        $contact = MarketingContact::where('unsubscribe_token', $token)->first();

        if ($contact) {
            // Update contact status
            $contact->update([
                'subscription_status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);

            // Add to permanent suppression list
            MarketingSuppression::suppress(
                $contact->email,
                'unsubscribe',
                'User requested unsubscribe via secure link/header',
                'user_unsubscribe'
            );

            // Audit log
            MarketingConsentLog::create([
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'action' => 'revoked',
                'source' => 'unsubscribe_page',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'notes' => 'User opted out of marketing emails.',
                'logged_at' => now(),
            ]);
        }

        // Return plain 200 for RFC 8058 automated post requests, or friendly view for web users
        if ($request->wantsJson() || $request->header('List-Unsubscribe') !== null) {
            return response()->json(['status' => 'success', 'message' => 'Unsubscribed successfully']);
        }

        return view('marketing.public.unsubscribe_success', [
            'contact' => $contact,
        ]);
    }

    /**
     * 1x1 Transparent GIF Open Tracking Pixel
     */
    public function trackOpen(int $id, Request $request)
    {
        $email = \App\Models\MarketingEmail::find($id);

        if ($email) {
            if ($email->status === 'sent' || $email->status === 'delivered') {
                $email->update([
                    'status' => 'opened',
                    'opened_at' => $email->opened_at ?? now(),
                ]);

                $email->contact?->increment('emails_opened_count');
                $email->campaign?->recalculateStats();
            }

            // Log event
            \App\Models\MarketingEmailEvent::create([
                'campaign_id' => $email->campaign_id,
                'contact_id' => $email->contact_id,
                'marketing_email_id' => $email->id,
                'ses_message_id' => $email->ses_message_id,
                'recipient_email' => $email->recipient_email,
                'event_type' => 'Open',
                'event_timestamp' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // 1x1 transparent GIF binary
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return response($gif, 200)->header('Content-Type', 'image/gif')->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
