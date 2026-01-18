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
        Schema::create('ad_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('creative_id')->nullable()->constrained('ad_creatives')->nullOnDelete();
            $table->enum('placement', [
                'home',
                'search_listings',
                'car_details',
                'auction_room',
                'live_stream_overlay'
            ])->nullable();
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->decimal('spend', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['date', 'campaign_id', 'creative_id', 'placement']);
            $table->index('date');
            $table->index(['campaign_id', 'date']);
            $table->index(['placement', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_daily_stats');
    }
};
