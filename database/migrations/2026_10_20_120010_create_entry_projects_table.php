<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained();
            $table->timestamps();

            // Prevent same identity+project+day at point entries (enforced at app level per spec)
            $table->unique(['entry_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_projects');
    }
};
