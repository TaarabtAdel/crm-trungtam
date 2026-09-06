<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('password');
            $table->string('phone')->nullable()->after('role');
            $table->foreignId('branch_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
    }
};
