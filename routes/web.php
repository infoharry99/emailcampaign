<?php

use App\Http\Controllers\Admin\MarketingAiCampaignController;
use App\Http\Controllers\Admin\MarketingAiWriterController;
use App\Http\Controllers\Admin\MarketingCampaignController;
use App\Http\Controllers\Admin\MarketingContactController;
use App\Http\Controllers\Admin\MarketingContactListController;
use App\Http\Controllers\Admin\MarketingDashboardController;
use App\Http\Controllers\Admin\MarketingEmailLogController;
use App\Http\Controllers\Admin\MarketingSettingsController;
use App\Http\Controllers\Admin\MarketingSuppressionController;
use App\Http\Controllers\Admin\MarketingTemplateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AwsSesWebhookController;
use App\Http\Controllers\MarketingPublicController;
use App\Http\Controllers\SystemUtilityController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('admin.marketing.dashboard');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public Marketing Tracking & Unsubscribe Endpoints
Route::prefix('marketing')->name('marketing.')->group(function () {
    Route::get('/unsubscribe/{token}', [MarketingPublicController::class, 'showUnsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe/{token}', [MarketingPublicController::class, 'processUnsubscribe'])->name('unsubscribe.post');
    Route::get('/track/open/{id}', [MarketingPublicController::class, 'trackOpen'])->name('track.open');
});

// AWS SES / SNS Webhook Endpoint (No CSRF)
Route::post('/webhooks/aws/ses', [AwsSesWebhookController::class, 'handle'])->name('webhooks.aws.ses');

// Protected Admin Panel Routes
Route::prefix('admin/marketing')->name('admin.marketing.')->middleware('auth')->group(function () {
    // 1. Dashboard
    Route::get('/dashboard', [MarketingDashboardController::class, 'index'])->name('dashboard');

    // 2. Contacts & Import
    Route::get('/contacts/import', [MarketingContactController::class, 'showImport'])->name('contacts.import');
    Route::post('/contacts/import', [MarketingContactController::class, 'processImport'])->name('contacts.import.process');
    Route::resource('contacts', MarketingContactController::class);

    // 2.5 Contact Lists
    Route::post('/lists/{list}/add-contact', [MarketingContactListController::class, 'addContact'])->name('lists.add-contact');
    Route::delete('/lists/{list}/remove-contact/{contact}', [MarketingContactListController::class, 'removeContact'])->name('lists.remove-contact');
    Route::resource('lists', MarketingContactListController::class);

    // 3. Campaigns
    Route::post('/campaigns/{campaign}/start', [MarketingCampaignController::class, 'start'])->name('campaigns.start');
    Route::post('/campaigns/{campaign}/pause', [MarketingCampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [MarketingCampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('/campaigns/{campaign}/cancel', [MarketingCampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('/campaigns/{campaign}/duplicate', [MarketingCampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::post('/campaigns/{campaign}/test-send', [MarketingCampaignController::class, 'testSend'])->name('campaigns.test-send');
    Route::resource('campaigns', MarketingCampaignController::class);

    // 3.5 AI Personalized Campaigns
    Route::post('/ai-campaigns/{campaign}/generate', [MarketingAiCampaignController::class, 'generate'])->name('ai-campaigns.generate');
    Route::post('/ai-campaigns/{campaign}/approve', [MarketingAiCampaignController::class, 'approve'])->name('ai-campaigns.approve');
    Route::put('/ai-campaigns/{campaign}/emails/{email}', [MarketingAiCampaignController::class, 'updateEmail'])->name('ai-campaigns.update-email');
    Route::post('/ai-campaigns/{campaign}/start', [MarketingAiCampaignController::class, 'start'])->name('ai-campaigns.start');
    Route::post('/ai-campaigns/{campaign}/test-send', [MarketingAiCampaignController::class, 'testSend'])->name('ai-campaigns.test-send');
    Route::resource('ai-campaigns', MarketingAiCampaignController::class);

    // 4. Email Templates
    Route::resource('templates', MarketingTemplateController::class);

    // 5. AI Email Writer
    Route::get('/ai-writer', [MarketingAiWriterController::class, 'index'])->name('ai-writer.index');
    Route::post('/ai-writer/generate', [MarketingAiWriterController::class, 'generate'])->name('ai-writer.generate');

    // 6. Email Logs
    Route::get('/logs', [MarketingEmailLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/{email}', [MarketingEmailLogController::class, 'show'])->name('logs.show');

    // 7. Suppression List
    Route::get('/suppressions', [MarketingSuppressionController::class, 'index'])->name('suppressions.index');
    Route::post('/suppressions', [MarketingSuppressionController::class, 'store'])->name('suppressions.store');
    Route::delete('/suppressions/{suppression}', [MarketingSuppressionController::class, 'destroy'])->name('suppressions.destroy');

    // 8. Settings
    Route::get('/settings/ses', [MarketingSettingsController::class, 'showSesSettings'])->name('settings.ses');
    Route::post('/settings/ses', [MarketingSettingsController::class, 'updateSesSettings'])->name('settings.ses.update');
    Route::get('/settings/openai', [MarketingSettingsController::class, 'showOpenAiSettings'])->name('settings.openai');
    Route::post('/settings/openai', [MarketingSettingsController::class, 'updateOpenAiSettings'])->name('settings.openai.update');

    // 9. System & Database Utilities
    Route::get('/system/migrate', [SystemUtilityController::class, 'migrate'])->name('system.migrate');
    Route::get('/system/routes/{action?}', [SystemUtilityController::class, 'routes'])->name('system.routes');
    Route::get('/system/artisan/{command}', [SystemUtilityController::class, 'run'])->name('system.artisan');
});
