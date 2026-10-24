<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->decimal('cost', 10, 2);
            $table->string('billing_cycle', 20);
            $table->date('renewal_date');
            $table->string('category', 30);
            $table->string('payment_method', 100)->nullable();
            $table->string('status', 20)->default('active');
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

        // Add check constraints for enum-like columns (PostgreSQL compatible)
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_billing_cycle_check CHECK (billing_cycle IN ('weekly', 'monthly', 'yearly'))");
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_category_check CHECK (category IN ('entertainment', 'productivity', 'fitness', 'utility', 'education', 'gaming', 'cloud_storage', 'other'))");
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status IN ('active', 'paused', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};