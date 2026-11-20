<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add business_description to marketing_contacts
        Schema::table('marketing_contacts', function (Blueprint $table) {
            $table->text('business_description')->nullable()->after('business_type');
        });

        // 2. Add campaign_type and contact_list_ids to marketing_campaigns
        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->string('campaign_type')->default('standard')->index()->after('name');
            $table->json('contact_list_ids')->nullable()->after('target_criteria');
        });

        // 3. Add is_approved and ai_rationale to marketing_emails
        Schema::table('marketing_emails', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->index()->after('status');
            $table->text('ai_rationale')->nullable()->after('ai_metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_emails', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'ai_rationale']);
        });

        Schema::table('marketing_campaigns', function (Blueprint $table) {
            $table->dropColumn(['campaign_type', 'contact_list_ids']);
        });

        Schema::table('marketing_contacts', function (Blueprint $table) {
            $table->dropColumn('business_description');
        });
    }
};
