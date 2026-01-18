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
        Schema::create('ad_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('creative_id')->constrained('ad_creatives')->cascadeOnDelete();
            $table->enum('placement', [
                'home',
                'search_listings',
                'car_details',
                'auction_room',
                'live_stream_overlay'
            ]);
            $table->string('position', 50)->nullable();
            $table->enum('event_type', ['impression', 'click', 'lead']);
            $table->decimal('cost_charged', 12, 4)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id_hash', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->dateTime('served_at');
            $table->dateTime('created_at');
            $table->json('context')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('invalid_reason', 255)->nullable();

            $table->index(['campaign_id', 'created_at']);
            $table->index(['creative_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['session_id_hash', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['placement', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_events');
    }
};
