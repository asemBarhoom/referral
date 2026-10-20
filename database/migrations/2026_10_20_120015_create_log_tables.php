<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('identity_number', 20);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->string('result', 40)->nullable(); // found, not_found, invalid
            $table->timestamp('looked_up_at');
            // no timestamps() — immutable; looked_up_at is the single timestamp
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 60); // created_beneficiary, confirmed_service, cancelled_visit, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('subject'); // what was acted on
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('occurred_at');
            // immutable — no update/delete, no updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('lookup_logs');
    }
};
