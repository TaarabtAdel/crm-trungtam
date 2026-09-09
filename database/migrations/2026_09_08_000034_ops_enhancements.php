<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('interest_subject_id')
                ->nullable()
                ->after('source')
                ->constrained('subjects')
                ->nullOnDelete();
            $table->string('interest_level', 50)->nullable()->after('interest_subject_id');
            $table->string('interest_note')->nullable()->after('interest_level');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('makeup_of_session_id')
                ->nullable()
                ->after('notes')
                ->constrained('class_sessions')
                ->nullOnDelete();
        });

        Schema::create('placement_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('interaction_id')->nullable()->constrained('interactions')->nullOnDelete();
            $table->foreignId('recommended_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('level', 50)->nullable();
            $table->date('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'tested_at']);
            $table->index(['student_id', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_tests');

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('makeup_of_session_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interest_subject_id');
            $table->dropColumn(['interest_level', 'interest_note']);
        });
    }
};
