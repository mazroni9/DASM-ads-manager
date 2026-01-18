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
        Schema::create('ad_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_wallet_id')->constrained('ad_wallets')->cascadeOnDelete();
            $table->enum('type', [
                'topup',
                'spend',
                'refund',
                'adjustment',
                'hold',
                'release'
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->foreignId('related_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('related_campaign_id')->nullable()->constrained('ad_campaigns')->nullOnDelete();
            $table->foreignId('related_event_id')->nullable()->constrained('ad_events')->nullOnDelete();
            $table->json('meta')->nullable()->comment('معلومات إضافية (سبب، تفاصيل...)');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['ad_wallet_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('related_campaign_id');
            $table->index('related_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_wallet_transactions');
    }
};
