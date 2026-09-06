<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_revenue', 14, 0)->default(0);
            $table->foreignId('assigned_sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('new');
            $table->timestamps();
        });

        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('leads');
    }
};
