<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('serial')->unique();
            $table->string('name')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('unassigned');
            $table->decimal('balance', 10, 2)->default(0);
            $table->date('next_due_at')->nullable();
            $table->text('bios_password')->nullable();
            $table->text('recovery_key')->nullable();
            $table->text('hmac_secret')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('next_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
