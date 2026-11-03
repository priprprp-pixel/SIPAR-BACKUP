<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $username = env('TECHNICIAN_USERNAME', 'teknisi');
        $email = env('TECHNICIAN_EMAIL', 'teknisi@sipar.sch.id');
        $account = DB::table('users')
            ->where('email', $email)
            ->orWhere('nis_nip', 'TEKNISI')
            ->orWhere('role', 'teknisi')
            ->first();

        if ($account) {
            DB::table('users')->where('id_user', $account->id_user)->update([
                'nis_nip' => $username,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', env('TECHNICIAN_EMAIL', 'teknisi@sipar.sch.id'))
            ->update(['nis_nip' => env('TECHNICIAN_USERNAME', 'teknisi'), 'updated_at' => now()]);
    }
};
