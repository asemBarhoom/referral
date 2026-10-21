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
            $table->string('identity_number', 9)->unique(); // 9-digit national ID (normalized)
            $table->string('name')->nullable(); // optional — no mandatory fields beyond ID
            $table->date('first_entry_date');   // Gaza-timezone date of first entry
            $table->timestamps();

            $table->index('identity_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
