<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $account = DB::table('users')
            ->where('email', 'teknisi@sipar.sch.id')
            ->orWhere('nis_nip', 'TEKNISI')
            ->first();

        if ($account) {
            DB::table('users')->where('id_user', $account->id_user)->update([
                'nama' => 'Teknisi SIPAR',
                'nis_nip' => 'TEKNISI',
                'password' => Hash::make('TEKNISI123'),
                'role' => 'teknisi',
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('users')->insert([
            'nama' => 'Teknisi SIPAR',
            'nis_nip' => 'TEKNISI',
            'email' => 'teknisi@sipar.sch.id',
            'password' => Hash::make('TEKNISI123'),
            'role' => 'teknisi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'teknisi@sipar.sch.id')->delete();
    }
};
