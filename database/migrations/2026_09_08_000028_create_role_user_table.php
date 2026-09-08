<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 50);
            $table->timestamps();
            $table->unique(['user_id', 'role']);
            $table->index('role');
        });

        $now = now();
        $rows = DB::table('users')
            ->select('id', 'role')
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->get()
            ->map(fn ($u) => [
                'user_id' => $u->id,
                'role' => $u->role,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('role_user')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
