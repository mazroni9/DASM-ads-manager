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
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'individual',
                'dealer',
                'company',
                'leasing_company',
                'bank',
                'insurance'
            ])->comment('نوع الكيان المالك');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('commercial_number')->nullable()->comment('الرقم التجاري للمنشآت');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();

            $table->index('type');
            $table->index('user_id');
            $table->unique(['type', 'commercial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
