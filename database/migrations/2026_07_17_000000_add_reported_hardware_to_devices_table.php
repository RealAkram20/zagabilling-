<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A device reports its own firmware identity when it enrolls, so the operator no
// longer has to read a serial off a sticker and type it in correctly. Serial
// therefore becomes optional at registration and is filled in by the device.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('manufacturer')->nullable()->after('model');
            $table->string('hostname')->nullable()->after('manufacturer');
        });

        // ->change() would need doctrine/dbal, which this project does not carry.
        // The unique index stays: MySQL permits many NULLs in a unique column.
        DB::statement('ALTER TABLE devices MODIFY serial VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['manufacturer', 'hostname']);
        });

        // Rows without a serial must go before the column can be NOT NULL again.
        DB::statement("DELETE FROM devices WHERE serial IS NULL");
        DB::statement('ALTER TABLE devices MODIFY serial VARCHAR(255) NOT NULL');
    }
};
