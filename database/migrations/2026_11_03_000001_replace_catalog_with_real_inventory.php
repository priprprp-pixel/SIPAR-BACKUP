<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $categories = collect(['Komputer', 'Penyimpanan', 'Multimedia', 'Mikrokontroler', 'Perkakas'])
            ->mapWithKeys(function (string $name): array {
                $category = DB::table('kategori_alat')->where('nama_kategori', $name)->first();
                $id = $category?->id_kategori ?? DB::table('kategori_alat')->insertGetId([
                    'nama_kategori' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [$name => $id];
            });

        $legacyCodes = ['LAN-001', 'RPL-002', 'RPL-003', 'RPL-004', 'RPL-005', 'RPL-006'];
        DB::table('alat')
            ->whereIn('kode_alat', $legacyCodes)
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('detail_peminjaman')
                    ->whereColumn('detail_peminjaman.id_alat', 'alat.id_alat');
            })
            ->delete();
        DB::table('kategori_alat')
            ->whereIn('nama_kategori', ['Jaringan', 'Perangkat Komputer', 'Perangkat Input', 'Media & Storage'])
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('alat')
                    ->whereColumn('alat.id_kategori', 'kategori_alat.id_kategori');
            })
            ->delete();

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
            $existing = DB::table('alat')->where('kode_alat', $code)->first();
            $values = [
                'nama_alat' => $name,
                'id_kategori' => $categories[$category],
                'stok_total' => $stock,
                'stok_tersedia' => $stock,
                'kondisi' => 'Baik',
                'status_alat' => 'Tersedia',
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('alat')->where('id_alat', $existing->id_alat)->update($values);
            } else {
                DB::table('alat')->insert($values + [
                    'kode_alat' => $code,
                    'qr_code' => (string) Str::uuid(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('alat')->whereIn('kode_alat', [
            'KOM-001', 'SIM-001', 'KOM-002', 'KOM-003', 'MUL-001', 'KOM-004',
            'PRK-001', 'MIK-001', 'PRK-002', 'PRK-003', 'PRK-004', 'MUL-002', 'MUL-003',
        ])->whereNotExists(function ($query): void {
            $query->select(DB::raw(1))
                ->from('detail_peminjaman')
                ->whereColumn('detail_peminjaman.id_alat', 'alat.id_alat');
        })->delete();
    }
};
