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
        Schema::create('ad_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->unique()->constrained('ad_accounts')->cascadeOnDelete();
            $table->decimal('balance_available', 12, 2)->default(0)->comment('الرصيد المتاح');
            $table->decimal('balance_hold', 12, 2)->default(0)->comment('الرصيد المعلّق للحملات');
            $table->decimal('lifetime_spent', 12, 2)->default(0)->comment('إجمالي الإنفاق مدى الحياة');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_wallets');
    }
};
