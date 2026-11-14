<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingSuppression;
use Illuminate\Http\Request;

class MarketingSuppressionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $reason = $request->get('reason');

        $query = MarketingSuppression::query();

        if ($search) {
            $query->where('email', 'like', "%{$search}%");
        }

        if ($reason) {
            $query->where('reason', $reason);
        }

        $suppressions = $query->latest()->paginate(25)->withQueryString();

        return view('marketing.suppressions.index', compact('suppressions', 'search', 'reason'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'reason' => 'required|in:hard_bounce,complaint,unsubscribe,manual',
            'details' => 'nullable|string|max:255',
        ]);

        MarketingSuppression::suppress(
            $request->input('email'),
            $request->input('reason'),
            $request->input('details', 'Manually added by administrator'),
            'admin_manual'
        );

        return back()->with('success', 'Email address added to suppression list.');
    }

    public function destroy(MarketingSuppression $suppression)
    {
        $suppression->delete();
        return back()->with('success', 'Email removed from suppression list.');
    }
}
