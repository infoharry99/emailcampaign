<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContact;
use App\Models\MarketingCampaign;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class MarketingAiWriterController extends Controller
{
    public function index()
    {
        $campaigns = MarketingCampaign::latest()->get();
        $sampleContacts = MarketingContact::latest()->limit(20)->get();

        return view('marketing.ai_writer.index', compact('campaigns', 'sampleContacts'));
    }

    public function generate(Request $request, OpenAIService $aiService)
    {
        $request->validate([
            'prospect_name' => 'nullable|string|max:100',
            'prospect_company' => 'nullable|string|max:100',
            'prospect_website' => 'nullable|string|max:200',
            'prospect_business_type' => 'nullable|string|max:100',
            'campaign_purpose' => 'required|string',
            'product_details' => 'nullable|string',
        ]);

        $mockContact = new MarketingContact([
            'name' => $request->input('prospect_name', 'Business Executive'),
            'company' => $request->input('prospect_company', 'Prospective Partner Inc'),
            'website' => $request->input('prospect_website', 'https://example.com'),
            'business_type' => $request->input('prospect_business_type', 'Enterprise'),
        ]);

        $mockCampaign = new MarketingCampaign([
            'purpose' => $request->input('campaign_purpose'),
            'product_service_details' => $request->input('product_details'),
        ]);

        $result = $aiService->generateMarketingEmail($mockContact, $mockCampaign);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
