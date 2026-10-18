<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_equipment_for_maintenance_preserves_borrowed_count_and_can_restore_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'LAN-STOCK', 'nama_alat' => 'LAN Tester', 'id_kategori' => $category->id_kategori,
            'stok_total' => 5, 'stok_tersedia' => 3, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-stock-state', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($admin)->put(route('admin.equipment.update', $equipment), [
            'kode_alat' => $equipment->kode_alat,
            'nama_alat' => $equipment->nama_alat,
            'id_kategori' => $category->id_kategori,
            'stok_total' => 5,
            'kondisi' => 'Baik',
            'status_alat' => 'Maintenance',
        ])->assertRedirect();

        $this->assertSame(0, $equipment->fresh()->stok_tersedia);
        $this->assertSame(3, $equipment->fresh()->stok_maintenance);
        $this->assertSame(0, $equipment->fresh()->stok_rusak);
        $this->assertSame(2, $equipment->fresh()->stok_total - $equipment->fresh()->stok_tersedia - $equipment->fresh()->stok_maintenance - $equipment->fresh()->stok_rusak);

        $this->put(route('admin.equipment.update', $equipment), [
            'kode_alat' => $equipment->kode_alat,
            'nama_alat' => $equipment->nama_alat,
            'id_kategori' => $category->id_kategori,
            'stok_total' => 5,
            'kondisi' => 'Rusak',
            'status_alat' => 'Rusak',
        ])->assertRedirect();

        $this->assertSame(3, $equipment->fresh()->stok_rusak);
        $this->assertSame(0, $equipment->fresh()->stok_maintenance);
        $this->assertSame(2, $equipment->fresh()->stok_total - $equipment->fresh()->stok_rusak);

        $this->put(route('admin.equipment.update', $equipment), [
            'kode_alat' => $equipment->kode_alat,
            'nama_alat' => $equipment->nama_alat,
            'id_kategori' => $category->id_kategori,
            'stok_total' => 5,
            'kondisi' => 'Baik',
            'status_alat' => 'Tersedia',
        ])->assertRedirect();

        $this->assertSame(3, $equipment->fresh()->stok_tersedia);
        $this->assertSame(0, $equipment->fresh()->stok_maintenance);
        $this->assertSame(0, $equipment->fresh()->stok_rusak);
        $this->assertSame('Tersedia', $equipment->fresh()->status_alat);
    }

    public function test_editing_an_available_item_does_not_silently_restore_maintenance_units(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'MIXED-STOCK', 'nama_alat' => 'Router', 'id_kategori' => $category->id_kategori,
            'stok_total' => 5, 'stok_tersedia' => 2, 'stok_maintenance' => 1, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-mixed-stock', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($admin)->put(route('admin.equipment.update', $equipment), [
            'kode_alat' => $equipment->kode_alat,
            'nama_alat' => 'Router Ruang Lab',
            'id_kategori' => $category->id_kategori,
            'stok_total' => 5,
            'kondisi' => 'Baik',
            'status_alat' => 'Tersedia',
        ])->assertRedirect();

        $this->assertSame(2, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $equipment->fresh()->stok_maintenance);
        $this->assertSame(2, $equipment->fresh()->stok_total - $equipment->fresh()->stok_tersedia - $equipment->fresh()->stok_maintenance - $equipment->fresh()->stok_rusak);
    }

    public function test_available_equipment_must_have_at_least_one_unit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'LAN-ZERO', 'nama_alat' => 'LAN Tester', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-lan-zero', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($admin)->post(route('admin.equipment.store'), [
            'kode_alat' => 'LAN-EMPTY',
            'nama_alat' => 'Alat Tanpa Unit',
            'id_kategori' => $category->id_kategori,
            'stok_total' => 0,
            'kondisi' => 'Baik',
            'status_alat' => 'Tersedia',
        ])->assertSessionHasErrors('stok_total');

        $this->assertDatabaseMissing('alat', ['kode_alat' => 'LAN-EMPTY']);

        $this->put(route('admin.equipment.update', $equipment), [
            'kode_alat' => $equipment->kode_alat,
            'nama_alat' => $equipment->nama_alat,
            'id_kategori' => $category->id_kategori,
            'stok_total' => 0,
            'kondisi' => 'Baik',
            'status_alat' => 'Tersedia',
        ])->assertSessionHasErrors('stok_total');

        $this->assertSame(2, $equipment->fresh()->stok_total);
        $this->assertSame(2, $equipment->fresh()->stok_tersedia);
    }
}
