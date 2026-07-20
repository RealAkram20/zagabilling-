<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('manufacturer')->nullable()->after('model');
            $table->string('hostname')->nullable()->after('manufacturer');
        });

        DB::statement('ALTER TABLE devices MODIFY serial VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['manufacturer', 'hostname']);
        });

        DB::statement("DELETE FROM devices WHERE serial IS NULL");
        DB::statement('ALTER TABLE devices MODIFY serial VARCHAR(255) NOT NULL');
    }
};
