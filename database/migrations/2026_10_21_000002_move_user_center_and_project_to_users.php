<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// One center per staff user and one project per point user, stored on users instead of pivot tables.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('center_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('center_id')->constrained()->nullOnDelete();
        });

        foreach (DB::table('users')->get(['id', 'role']) as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'center_id' => $user->role === 'admin'
                    ? null
                    : DB::table('user_centers')->where('user_id', $user->id)->orderBy('id')->value('center_id'),
                'project_id' => $user->role === 'point'
                    ? DB::table('user_projects')->where('user_id', $user->id)->orderBy('id')->value('project_id')
                    : null,
            ]);
        }

        Schema::dropIfExists('user_projects');
        Schema::dropIfExists('user_centers');

        DB::table('settings')->where('key', 'idle_timeout')->delete();
    }

    public function down(): void
    {
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
            $table->timestamps();
            $table->unique(['user_id', 'project_id']);
        });

        foreach (DB::table('users')->get(['id', 'center_id', 'project_id']) as $user) {
            if ($user->center_id) {
                DB::table('user_centers')->insert(['user_id' => $user->id, 'center_id' => $user->center_id, 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($user->project_id) {
                DB::table('user_projects')->insert(['user_id' => $user->id, 'project_id' => $user->project_id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('center_id');
        });
    }
};
