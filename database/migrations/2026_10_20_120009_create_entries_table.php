<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained();
            $table->foreignId('staff_id')->constrained('users');
            $table->string('type', 20); // reception, point
            $table->boolean('is_new')->default(false); // first time in the system
            $table->timestamp('entry_time');   // server time (Gaza)
            $table->date('entry_date');        // Gaza-timezone date
            $table->string('idempotency_key', 64)->unique()->nullable();
            $table->timestamps();

            $table->index(['beneficiary_id', 'entry_date', 'center_id']);
            $table->index(['center_id', 'entry_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
