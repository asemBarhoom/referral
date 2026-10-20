<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('visit_number'); // sequential per beneficiary
            $table->string('type', 20); // new, followup
            $table->foreignId('center_id')->constrained();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('staff_id')->constrained('users');
            $table->timestamp('visit_time'); // server time
            $table->date('visit_date');      // Gaza timezone date
            $table->string('status', 20)->default('valid'); // valid, cancelled
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->unique()->nullable(); // prevent double-submit
            $table->timestamps();

            $table->index(['beneficiary_id', 'visit_date', 'center_id']);
            $table->index(['center_id', 'visit_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_visits');
    }
};
