<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('bank_bin', 20)->nullable()->after('phone');
            $table->string('bank_account_number', 50)->nullable()->after('bank_bin');
            $table->string('bank_account_name', 120)->nullable()->after('bank_account_number');
            $table->string('bank_name', 120)->nullable()->after('bank_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['bank_bin', 'bank_account_number', 'bank_account_name', 'bank_name']);
        });
    }
};
