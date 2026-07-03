<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unlock_codes', function (Blueprint $table) {
            $table->unsignedInteger('counter')->nullable()->after('code');
            $table->unsignedSmallInteger('duration_days')->nullable()->after('counter');
        });
    }

    public function down(): void
    {
        Schema::table('unlock_codes', function (Blueprint $table) {
            $table->dropColumn(['counter', 'duration_days']);
        });
    }
};
