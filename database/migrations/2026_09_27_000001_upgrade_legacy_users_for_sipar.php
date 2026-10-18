<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'id') && ! Schema::hasColumn('users', 'id_user')) {
            Schema::table('users', fn (Blueprint $table) => $table->renameColumn('id', 'id_user'));
        }

        if (Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'nama')) {
            Schema::table('users', fn (Blueprint $table) => $table->renameColumn('name', 'nama'));
        }

        if (! Schema::hasColumn('users', 'nis_nip')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('nis_nip')->nullable());
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->enum('role', ['admin', 'siswa'])->default('siswa'));
        }

        if (Schema::hasColumn('users', 'nis_nip')) {
            DB::table('users')->whereNull('nis_nip')->orderBy('id_user')->get(['id_user'])->each(function ($user): void {
                DB::table('users')->where('id_user', $user->id_user)->update(['nis_nip' => 'LEGACY-'.$user->id_user]);
            });

            $hasUniqueIndex = collect(Schema::getIndexes('users'))->contains(fn (array $index): bool => ($index['name'] ?? null) === 'users_nis_nip_unique');
            if (! $hasUniqueIndex) {
                Schema::table('users', fn (Blueprint $table) => $table->unique('nis_nip'));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $hasUniqueIndex = collect(Schema::getIndexes('users'))->contains(fn (array $index): bool => ($index['name'] ?? null) === 'users_nis_nip_unique');
        if ($hasUniqueIndex) {
            Schema::table('users', fn (Blueprint $table) => $table->dropUnique('users_nis_nip_unique'));
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }
        if (Schema::hasColumn('users', 'nis_nip')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('nis_nip'));
        }
        if (Schema::hasColumn('users', 'nama') && ! Schema::hasColumn('users', 'name')) {
            Schema::table('users', fn (Blueprint $table) => $table->renameColumn('nama', 'name'));
        }
        if (Schema::hasColumn('users', 'id_user') && ! Schema::hasColumn('users', 'id')) {
            Schema::table('users', fn (Blueprint $table) => $table->renameColumn('id_user', 'id'));
        }
    }
};
