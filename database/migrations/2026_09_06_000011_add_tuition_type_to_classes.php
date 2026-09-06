<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('tuition_type')->default('monthly')->after('tuition_fee');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('sessions_count')->nullable()->after('billing_month');
            $table->string('fee_type')->nullable()->after('sessions_count');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('tuition_type');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['sessions_count', 'fee_type']);
        });
    }
};
