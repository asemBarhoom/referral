<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = 'أدخل رقم هوية ولي الأمر، ثم اختر المشاريع التي ستتوجه لها الحالة.';
    private const NEW = 'أدخل رقم هوية الحالة، ثم اختر المشاريع التي ستتوجه لها الحالة.';

    // Only replaces the seeded default, so a hint edited by an admin is kept.
    public function up(): void
    {
        DB::table('settings')->where('key', 'hint_reception')->where('value', self::OLD)->update(['value' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'hint_reception')->where('value', self::NEW)->update(['value' => self::OLD]);
    }
};
