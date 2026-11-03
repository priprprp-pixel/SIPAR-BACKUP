<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@sipar.sch.id'], [
            'nama' => 'Agus Toolman', 'nis_nip' => 'ADMIN',
            'password' => Hash::make('ADMIN123'), 'role' => 'admin',
        ]);
        User::updateOrCreate(['email' => 'teknisi@sipar.sch.id'], [
            'nama' => 'Teknisi SIPAR', 'nis_nip' => 'TEKNISI',
            'password' => Hash::make('TEKNISI123'), 'role' => 'teknisi',
        ]);
        User::firstOrCreate(['email' => 'siswa@sipar.sch.id'], [
            'nama' => 'Rangga Saputra', 'nis_nip' => '20230001',
            'password' => Hash::make('password123'), 'role' => 'siswa',
        ]);

        $categories = collect(['Komputer', 'Penyimpanan', 'Multimedia', 'Mikrokontroler', 'Perkakas'])
            ->mapWithKeys(fn ($name) => [$name => KategoriAlat::firstOrCreate(['nama_kategori' => $name])]);
        $equipment = [
            ['KOM-001', 'Laptop HP', 'Komputer', 3],
            ['SIM-001', 'Flashdisk', 'Penyimpanan', 10],
            ['KOM-002', 'Mouse', 'Komputer', 20],
            ['KOM-003', 'Keyboard', 'Komputer', 10],
            ['MUL-001', 'Proyektor', 'Multimedia', 3],
            ['KOM-004', 'Monitor', 'Komputer', 3],
            ['PRK-001', 'Obeng mini (1 set)', 'Perkakas', 1],
            ['MIK-001', 'Arduino Uno', 'Mikrokontroler', 30],
            ['PRK-002', 'Solder', 'Perkakas', 1],
            ['PRK-003', 'Bor tembok', 'Perkakas', 1],
            ['PRK-004', 'Bor mini listrik', 'Perkakas', 1],
            ['MUL-002', 'Spiker', 'Multimedia', 1],
            ['MUL-003', 'Mic + sound', 'Multimedia', 1],
        ];

        foreach ($equipment as [$code, $name, $category, $stock]) {
            Alat::firstOrCreate(['kode_alat' => $code], [
                'nama_alat' => $name, 'id_kategori' => $categories[$category]->id_kategori,
                'stok_total' => $stock, 'stok_tersedia' => $stock, 'kondisi' => 'Baik',
                'deskripsi' => null, 'qr_code' => (string) Str::uuid(), 'status_alat' => 'Tersedia',
            ]);
        }
    }
}
