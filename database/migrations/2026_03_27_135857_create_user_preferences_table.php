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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('accept_terms')->default(false);
            $table->boolean('allow_marketing_emails')->default(false);
            $table->boolean('allow_marketing_sms')->default(false);
            $table->boolean('allow_third_party_share')->default(false);
            $table->boolean('receive_newsletter')->default(true);
            $table->string('preferred_contact_channel')->default('email');
            $table->boolean('auto_reject_cookies')->default(true);
            $table->boolean('pause_on_captcha')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
