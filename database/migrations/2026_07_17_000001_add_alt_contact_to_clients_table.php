<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('alt_contact_name')->nullable()->after('phone');
            $table->string('alt_contact_phone', 40)->nullable()->after('alt_contact_name');
            $table->string('alt_contact_relationship')->nullable()->after('alt_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['alt_contact_name', 'alt_contact_phone', 'alt_contact_relationship']);
        });
    }
};
