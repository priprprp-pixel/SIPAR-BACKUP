<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiparSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sipar_tables_migrate_with_the_expected_primary_keys_and_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['id_user', 'nama', 'nis_nip', 'email', 'email_verified_at', 'role']));
        $this->assertTrue(Schema::hasColumns('kategori_alat', ['id_kategori', 'nama_kategori']));
        $this->assertTrue(Schema::hasColumns('alat', ['id_alat', 'kode_alat', 'stok_total', 'stok_tersedia', 'qr_code', 'status_alat']));
        $this->assertTrue(Schema::hasColumns('peminjaman', ['id_peminjaman', 'id_user', 'status']));
        $this->assertTrue(Schema::hasColumns('detail_peminjaman', ['id_detail', 'id_peminjaman', 'id_alat', 'jumlah']));
        $this->assertTrue(Schema::hasColumns('pengembalian', ['id_pengembalian', 'id_peminjaman', 'denda']));
        $this->assertTrue(Schema::hasColumns('alat', ['stok_maintenance']));
        $this->assertTrue(Schema::hasColumns('alat', ['stok_rusak']));
        $this->assertTrue(Schema::hasColumns('detail_pengembalian', ['id_detail_pengembalian', 'id_detail', 'jumlah', 'kondisi_alat', 'denda']));

        $equipmentIndexes = collect(Schema::getIndexes('alat'));
        $this->assertTrue($equipmentIndexes->contains(fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['kode_alat']));
        $this->assertTrue($equipmentIndexes->contains(fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['qr_code']));

        $loanForeignKeys = collect(Schema::getForeignKeys('peminjaman'));
        $this->assertTrue($loanForeignKeys->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['id_user']
            && ($key['foreign_table'] ?? null) === 'users'
            && ($key['foreign_columns'] ?? []) === ['id_user']));

        $detailForeignKeys = collect(Schema::getForeignKeys('detail_peminjaman'));
        $this->assertTrue($detailForeignKeys->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['id_peminjaman']
            && ($key['foreign_table'] ?? null) === 'peminjaman'));
        $this->assertTrue($detailForeignKeys->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['id_alat']
            && ($key['foreign_table'] ?? null) === 'alat'));
    }
}
