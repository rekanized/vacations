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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('location')->index();
            $table->string('holiday_country', 2)->default('SE');
            $table->string('theme_preference', 16)->default('light');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'id']);
            $table->index(['is_active', 'name']);
            $table->index(['is_active', 'location']);
            $table->index(['department_id', 'is_active', 'name']);
            $table->index(['manager_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};