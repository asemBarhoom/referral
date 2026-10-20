<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users');
            $table->timestamp('service_time');
            $table->date('service_date'); // Gaza timezone
            $table->string('source', 30); // reception_confirmed, exceptional
            // classification codes: MATCHED, DUPLICATE_SERVICE, NOT_REFERRED_TODAY, etc.
            $table->string('match_code', 40);
            $table->boolean('is_exceptional')->default(false);
            $table->foreignId('exception_reason_id')->nullable()->constrained('reason_options')->nullOnDelete();
            $table->text('exception_note')->nullable();
            // pending, approved, rejected
            $table->string('review_status', 20)->default('approved');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('idempotency_key', 64)->unique()->nullable();
            $table->timestamps();

            $table->index(['beneficiary_id', 'project_id', 'service_date']);
            $table->index(['center_id', 'service_date', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_records');
    }
};
