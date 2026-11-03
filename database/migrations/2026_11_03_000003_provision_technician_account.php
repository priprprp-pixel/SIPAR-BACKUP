<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $password = env('TECHNICIAN_PASSWORD');
        if (! is_string($password) || strlen($password) < 10) {
            throw new RuntimeException('TECHNICIAN_PASSWORD wajib diatur dan minimal 10 karakter sebelum migrasi dijalankan.');
        }
        $username = env('TECHNICIAN_USERNAME', 'teknisi');
        $email = env('TECHNICIAN_EMAIL', 'teknisi@sipar.sch.id');
        $account = DB::table('users')
            ->where('email', $email)
            ->orWhere('nis_nip', $username)
            ->first();

        if ($account) {
            DB::table('users')->where('id_user', $account->id_user)->update([
                'nama' => 'Teknisi SIPAR',
                'nis_nip' => $username,
                'password' => Hash::make($password),
                'role' => 'teknisi',
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('users')->insert([
            'nama' => 'Teknisi SIPAR',
            'nis_nip' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'teknisi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', env('TECHNICIAN_EMAIL', 'teknisi@sipar.sch.id'))->delete();
    }
};
