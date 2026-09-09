<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope', 16); // teacher|staff
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('billing_month', 7); // Y-m
            $table->string('type', 16); // bonus|penalty|advance
            $table->decimal('amount', 15, 0);
            $table->text('note');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->uuid('batch_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope', 'billing_month']);
            $table->index(['teacher_id', 'billing_month']);
            $table->index(['user_id', 'billing_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
