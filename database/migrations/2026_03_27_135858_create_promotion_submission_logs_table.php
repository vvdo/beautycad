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
        Schema::create('promotion_submission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_submission_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('level')->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['promotion_submission_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_submission_logs');
    }
};
