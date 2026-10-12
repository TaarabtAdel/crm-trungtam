<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            $table->date('enrolled_from')->nullable()->after('student_id');
            $table->date('enrolled_to')->nullable()->after('enrolled_from');
        });
    }

    public function down(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            $table->dropColumn(['enrolled_from', 'enrolled_to']);
        });
    }
};
