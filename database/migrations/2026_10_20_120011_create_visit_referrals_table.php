<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('reception_visits')->cascadeOnDelete();
            $table->foreignId('beneficiary_project_id')->constrained('beneficiary_projects')->cascadeOnDelete();
            $table->unsignedInteger('project_visit_number')->nullable(); // sequential per beneficiary+project
            $table->timestamps();

            $table->unique(['visit_id', 'beneficiary_project_id']);
            $table->index('beneficiary_project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_referrals');
    }
};
