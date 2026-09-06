<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('related_name')->nullable()->after('email');
            $table->string('related_phone', 30)->nullable()->after('related_name');
            $table->string('related_email')->nullable()->after('related_phone');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['related_name', 'related_phone', 'related_email']);
        });
    }
};
