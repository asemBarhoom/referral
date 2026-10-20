<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            // warning, under_review, temporary_block, permanent_block
            $table->string('level', 30);
            $table->text('reason');
            $table->foreignId('reason_option_id')->nullable()->constrained('reason_options')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable(); // null = permanent
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['beneficiary_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restrictions');
    }
};
