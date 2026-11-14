<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_contact_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_contact_list_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('marketing_contacts')->onDelete('cascade');
            $table->foreignId('contact_list_id')->constrained('marketing_contact_lists')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['contact_id', 'contact_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_contact_list_members');
        Schema::dropIfExists('marketing_contact_lists');
    }
};
