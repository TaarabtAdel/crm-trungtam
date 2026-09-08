<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('daily_rate', 12, 0)->default(0)->after('phone');
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->string('status', 20)->default('present'); // present|half|leave|absent
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('teacher_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::dropIfExists('staff_attendances');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};
