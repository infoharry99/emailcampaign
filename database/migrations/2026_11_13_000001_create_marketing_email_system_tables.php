<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Marketing Contacts
        Schema::create('marketing_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('business_type')->nullable();
            
            // Email verification status: unverified, valid, invalid, risky
            $table->string('verification_status')->default('unverified')->index();
            
            // Marketing consent status: granted, revoked, pending
            $table->string('consent_status')->default('pending')->index();
            $table->timestamp('consent_date')->nullable();
            $table->string('consent_source')->nullable(); // e.g., webform, checkout, api, csv_import
            $table->text('consent_proof')->nullable(); // IP, form details, agreement text
            
            // Subscription status: subscribed, unsubscribed
            $table->string('subscription_status')->default('subscribed')->index();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();

            // Tracking aggregates
            $table->unsignedInteger('emails_sent_count')->default(0);
            $table->unsignedInteger('emails_opened_count')->default(0);
            $table->unsignedInteger('emails_clicked_count')->default(0);
            $table->timestamp('last_emailed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Marketing Templates
        Schema::create('marketing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('content'); // HTML with {{name}}, {{company}}, {{website}}, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Marketing Campaigns
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('purpose')->nullable(); // Objectives & background for AI prompt
            $table->text('product_service_details')->nullable(); // Features/offer details for AI prompt
            $table->string('target_criteria')->nullable(); // e.g. all_subscribed, business_type:tech, etc.
            
            $table->string('subject')->nullable(); // Default/template subject line
            $table->longText('content')->nullable(); // Template content if template/both mode
            
            $table->date('sending_date')->nullable();
            $table->time('sending_time')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            
            // Sending mode: ai, template, both
            $table->string('sending_mode')->default('ai')->index();
            
            // Status: draft, scheduled, running, paused, completed, cancelled
            $table->string('status')->default('draft')->index();
            
            // Aggregated counters
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('generated_count')->default(0);
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('complained_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 4. Marketing Emails (Individual dispatched or prepared email per contact in campaign)
        Schema::create('marketing_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('marketing_contacts')->onDelete('cascade');
            
            $table->string('recipient_email')->index();
            $table->string('recipient_name')->nullable();
            
            $table->string('subject');
            $table->longText('body_html');
            $table->text('body_text')->nullable();
            
            // Generation mode
            $table->string('generation_mode')->default('ai'); // ai, template, custom
            $table->boolean('is_ai_generated')->default(false);
            $table->text('ai_prompt')->nullable();
            $table->json('ai_metadata')->nullable(); // Tokens, model used, estimated cost
            
            // SES Dispatch metadata
            $table->string('ses_message_id')->nullable()->index();
            
            // Status: pending, generated, queued, sent, delivered, opened, clicked, bounced, failed, complained, unsubscribed, suppressed
            $table->string('status')->default('pending')->index();
            $table->string('error_message')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            
            // Timestamps
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            
            $table->timestamps();

            // Prevent duplicate marketing email to same contact in same campaign
            $table->unique(['campaign_id', 'contact_id']);
        });

        // 5. Marketing Email Events (Granular event logs from SES & Tracking)
        Schema::create('marketing_email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('marketing_contacts')->nullOnDelete();
            $table->foreignId('marketing_email_id')->nullable()->constrained('marketing_emails')->nullOnDelete();
            
            $table->string('ses_message_id')->nullable()->index();
            $table->string('recipient_email')->index();
            
            // Event type: Send, Delivery, Open, Click, Bounce, Complaint, Reject, RenderingFailure
            $table->string('event_type')->index();
            $table->timestamp('event_timestamp')->useCurrent();
            
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('clicked_url')->nullable();
            
            $table->json('payload')->nullable(); // Full raw SES/SNS payload or metadata
            $table->timestamps();
        });

        // 6. Marketing Suppressions (Global suppression list for bounces, complaints, manual/unsub)
        Schema::create('marketing_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            // Reason: hard_bounce, complaint, unsubscribe, manual
            $table->string('reason')->index();
            $table->string('details')->nullable(); // SES bounce subtype or note
            $table->string('source')->nullable(); // sns_webhook, admin_manual, user_unsubscribe
            $table->timestamps();
        });

        // 7. Marketing Consent Logs (Documented audit trail of permissions)
        Schema::create('marketing_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('marketing_contacts')->onDelete('cascade');
            $table->string('email')->index();
            $table->string('action'); // granted, revoked, confirmed, imported
            $table->string('source'); // webform, checkout, api, csv_import, manual_admin
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });

        // 8. Marketing Settings (Key-value store for SES, OpenAI, Rate limits, etc.)
        Schema::create('marketing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_settings');
        Schema::dropIfExists('marketing_consent_logs');
        Schema::dropIfExists('marketing_suppressions');
        Schema::dropIfExists('marketing_email_events');
        Schema::dropIfExists('marketing_emails');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('marketing_templates');
        Schema::dropIfExists('marketing_contacts');
    }
};
