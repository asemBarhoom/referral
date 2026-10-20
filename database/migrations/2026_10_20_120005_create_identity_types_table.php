<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');         // internal name e.g. mother, father, child
            $table->string('printed_name'); // display name in Arabic
            $table->boolean('is_system')->default(false); // system types vs admin-added
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_types');
    }
};
