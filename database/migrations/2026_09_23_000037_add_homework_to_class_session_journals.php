<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_session_journals', function (Blueprint $table) {
            if (! Schema::hasColumn('class_session_journals', 'homework')) {
                $table->text('homework')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_session_journals', function (Blueprint $table) {
            if (Schema::hasColumn('class_session_journals', 'homework')) {
                $table->dropColumn('homework');
            }
        });
    }
};
