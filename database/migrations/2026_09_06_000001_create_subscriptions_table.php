<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->decimal('cost', 10, 2);
            $table->enum('billing_cycle', ['weekly', 'monthly', 'yearly']);
            $table->date('renewal_date');
            $table->enum('category', [
                'entertainment',
                'productivity',
                'fitness',
                'utility',
                'education',
                'gaming',
                'cloud_storage',
                'other',
            ]);
            $table->string('payment_method', 100)->nullable();
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->boolean('is_trial')->default(false);
            $table->date('trial_end_date')->nullable();
            $table->date('last_used_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('billing_cycle');
            $table->index('category');
            $table->index('status');
            $table->index('renewal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};