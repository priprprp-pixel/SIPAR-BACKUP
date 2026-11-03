<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $account = DB::table('users')
            ->where('email', 'teknisi@sipar.sch.id')
            ->orWhere('nis_nip', 'TEKNISI')
            ->orWhere('role', 'teknisi')
            ->first();

        if ($account) {
            DB::table('users')->where('id_user', $account->id_user)->update([
                'nis_nip' => 'teknisi',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'teknisi@sipar.sch.id')
            ->update(['nis_nip' => 'TEKNISI', 'updated_at' => now()]);
    }
};
