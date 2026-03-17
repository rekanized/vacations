<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_request_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_uuid')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30)->index();
            $table->string('absence_type', 20)->nullable();
            $table->string('status', 30)->nullable()->index();
            $table->date('date_start')->nullable()->index();
            $table->date('date_end')->nullable();
            $table->unsignedInteger('date_count')->default(0);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_request_logs');
    }
};
