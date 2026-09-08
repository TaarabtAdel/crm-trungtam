<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->decimal('teacher_hourly_rate', 12, 0)
                ->nullable()
                ->after('teacher_id')
                ->comment('Null = dùng mức hourly_rate của giáo viên');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('teacher_hourly_rate');
        });
    }
};
