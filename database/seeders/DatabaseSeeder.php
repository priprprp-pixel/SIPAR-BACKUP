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
        User::firstOrCreate(['email' => 'siswa@sipar.sch.id'], [
            'nama' => 'Rangga Saputra', 'nis_nip' => '20230001',
            'password' => Hash::make('password123'), 'role' => 'siswa',
        ]);

        $categories = collect(['Jaringan', 'Perangkat Komputer', 'Perangkat Input', 'Media & Storage'])
            ->mapWithKeys(fn ($name) => [$name => KategoriAlat::firstOrCreate(['nama_kategori' => $name])]);
        $equipment = [
            ['LAN-001', 'LAN Tester Digital', 'Jaringan', 5],
            ['RPL-002', 'Router MikroTik', 'Jaringan', 4],
            ['RPL-003', 'Laptop Development', 'Perangkat Komputer', 3],
            ['RPL-004', 'Crimping Tool Set', 'Perangkat Input', 8],
            ['RPL-005', 'Keyboard Mekanik', 'Perangkat Input', 6],
            ['RPL-006', 'SSD Eksternal 1TB', 'Media & Storage', 7],
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
