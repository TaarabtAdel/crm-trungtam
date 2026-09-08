<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('gross_amount', 14, 0)->nullable()->after('amount');
            $table->decimal('discount_amount', 14, 0)->default(0)->after('gross_amount');
            $table->string('discount_reason')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'discount_amount', 'discount_reason']);
        });
    }
};
