<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_session_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->unique()->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('session_date');
            $table->string('class_name');
            $table->unsignedInteger('enrollment_count')->default(0);
            $table->unsignedInteger('present_count')->default(0);
            $table->unsignedInteger('absent_count')->default(0);
            $table->unsignedInteger('excused_count')->default(0);
            $table->unsignedInteger('late_count')->default(0);
            $table->string('lesson_title')->nullable();
            $table->text('content')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('filled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();

            $table->index(['class_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_journals');
    }
};
