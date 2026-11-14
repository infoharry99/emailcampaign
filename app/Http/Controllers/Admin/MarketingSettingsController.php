<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingSetting;
use App\Services\AwsSesService;
use Illuminate\Http\Request;

class MarketingSettingsController extends Controller
{
    public function showSesSettings(AwsSesService $sesService)
    {
        $settings = [
            'aws_access_key_id' => MarketingSetting::get('aws_access_key_id', config('services.ses.key')),
            'aws_secret_access_key' => MarketingSetting::get('aws_secret_access_key', config('services.ses.secret')),
            'aws_region' => MarketingSetting::get('aws_region', config('services.ses.region', 'ap-south-1')),
            'aws_ses_configuration_set' => MarketingSetting::get('aws_ses_configuration_set', config('services.ses.configuration_set', '')),
            'aws_ses_from_address' => MarketingSetting::get('aws_ses_from_address', config('services.ses.from_address', 'noreply@marketing.thenexteck.com')),
            'aws_ses_from_name' => MarketingSetting::get('aws_ses_from_name', config('services.ses.from_name', 'TheNexteck Marketing')),
            'aws_ses_sending_rate' => MarketingSetting::get('aws_ses_sending_rate', config('services.ses.sending_rate', 14)),
            'aws_ses_daily_quota' => MarketingSetting::get('aws_ses_daily_quota', config('services.ses.daily_quota', 50000)),
        ];

        $sesStats = $sesService->getSendStatistics();

        return view('marketing.settings.ses', compact('settings', 'sesStats'));
    }

    public function updateSesSettings(Request $request)
    {
        $validated = $request->validate([
            'aws_access_key_id' => 'nullable|string|max:100',
            'aws_secret_access_key' => 'nullable|string|max:150',
            'aws_region' => 'required|string|max:50',
            'aws_ses_configuration_set' => 'nullable|string|max:100',
            'aws_ses_from_address' => 'required|email|max:150',
            'aws_ses_from_name' => 'required|string|max:100',
            'aws_ses_sending_rate' => 'required|numeric|min:1|max:100',
            'aws_ses_daily_quota' => 'required|numeric|min:1',
        ]);

        foreach ($validated as $key => $value) {
            MarketingSetting::set($key, (string) $value);
        }

        return back()->with('success', 'Amazon SES settings updated successfully.');
    }

    public function showOpenAiSettings()
    {
        $settings = [
            'openai_api_key' => MarketingSetting::get('openai_api_key', config('services.openai.api_key')),
            'openai_model' => MarketingSetting::get('openai_model', config('services.openai.model', 'gpt-4o-mini')),
            'openai_max_tokens' => MarketingSetting::get('openai_max_tokens', config('services.openai.max_tokens', 450)),
            'openai_temperature' => MarketingSetting::get('openai_temperature', config('services.openai.temperature', 0.7)),
            'openai_total_tokens_used' => MarketingSetting::get('openai_total_tokens_used', 0),
            'openai_total_cost_usd' => MarketingSetting::get('openai_total_cost_usd', 0.0),
        ];

        return view('marketing.settings.openai', compact('settings'));
    }

    public function updateOpenAiSettings(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => 'nullable|string|max:150',
            'openai_model' => 'required|string|max:50',
            'openai_max_tokens' => 'required|numeric|min:50|max:2000',
            'openai_temperature' => 'required|numeric|min:0|max:1',
        ]);

        foreach ($validated as $key => $value) {
            MarketingSetting::set($key, (string) $value);
        }

        return back()->with('success', 'OpenAI API configuration updated successfully.');
    }
}
