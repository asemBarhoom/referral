<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('identity_number', 20)->unique(); // 9-digit national ID (normalized)
            $table->foreignId('identity_type_id')->constrained();
            $table->string('name');
            $table->string('name_normalized'); // for search
            $table->string('phone', 20);
            $table->date('dob')->nullable(); // required for children only
            $table->foreignId('registration_center_id')->constrained('centers');
            $table->foreignId('family_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->string('status', 20)->default('active'); // active, merged
            $table->foreignId('merged_into_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->timestamps();

            $table->index('phone');
            $table->index('name_normalized');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
