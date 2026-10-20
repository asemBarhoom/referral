<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend users with staff profile info
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable(); // reception, project_staff, center_admin, dashboard_admin
            $table->boolean('is_active')->default(true)->after('email');
        });

        Schema::create('user_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'center_id']);
        });

        Schema::create('user_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_id', 'center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_projects');
        Schema::dropIfExists('user_centers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
