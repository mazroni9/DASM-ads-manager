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
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->enum('objective', ['views', 'traffic', 'leads']);
            $table->enum('pricing_model', ['CPC', 'CPM']);
            $table->enum('status', [
                'draft',
                'pending',
                'active',
                'paused',
                'rejected',
                'ended',
                'budget_exhausted'
            ])->default('draft');
            $table->decimal('daily_budget', 12, 2);
            $table->decimal('total_budget', 12, 2)->nullable();
            $table->decimal('daily_spent', 12, 2)->default(0);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->json('targeting')->comment('Targeting Schema v1');
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('ad_account_id');
            $table->index('status');
            $table->index('pricing_model');
            $table->index(['start_at', 'end_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
