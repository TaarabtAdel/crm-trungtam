<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->foreignId('student_id')
                ->nullable()
                ->after('assigned_sales_id')
                ->constrained('students')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
            $table->string('phone')->nullable(false)->change();
        });
    }
};
