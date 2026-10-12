<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            $table->date('enrolled_from')->nullable()->after('student_id');
            $table->date('enrolled_to')->nullable()->after('enrolled_from');
        });

        if (Schema::hasColumn('class_student', 'created_at')) {
            DB::table('class_student')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->enrolled_from) {
                        continue;
                    }
                    $from = $row->created_at
                        ? \Carbon\Carbon::parse($row->created_at)->toDateString()
                        : now()->toDateString();
                    DB::table('class_student')->where('id', $row->id)->update(['enrolled_from' => $from]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('class_student', function (Blueprint $table) {
            $table->dropColumn(['enrolled_from', 'enrolled_to']);
        });
    }
};
