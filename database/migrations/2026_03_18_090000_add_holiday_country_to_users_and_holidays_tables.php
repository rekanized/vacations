<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('holiday_country', 2)->default('SE')->after('location');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->string('country_code', 2)->default('SE')->after('id');
        });

        DB::table('holidays')->update(['country_code' => 'SE']);

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique('holidays_date_unique');
            $table->unique(['country_code', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique(['country_code', 'date']);
            $table->dropColumn('country_code');
            $table->unique('date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('holiday_country');
        });
    }
};