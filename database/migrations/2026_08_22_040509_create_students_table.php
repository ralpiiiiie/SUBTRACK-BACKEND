<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->smallInteger('age');
            $table->string('course', 100);
            $table->smallInteger('year_level');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['first_name', 'last_name']);
            $table->index('course');
            $table->index('status');
        });

        // Add check constraint for status (PostgreSQL compatible)
        DB::statement("ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status IN ('active', 'inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};