<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40); // e.g. service_anomaly, new_project_enrollment, wrong_center
            $table->string('priority', 10)->default('normal'); // normal, high
            $table->morphs('source'); // polymorphic: service_record, beneficiary_project, etc.
            $table->foreignId('center_id')->constrained();
            $table->foreignId('beneficiary_id')->nullable()->constrained()->nullOnDelete();
            $table->string('decision', 20)->nullable(); // approved, rejected, transferred, merged
            $table->foreignId('decision_maker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['center_id', 'decision', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_items');
    }
};
