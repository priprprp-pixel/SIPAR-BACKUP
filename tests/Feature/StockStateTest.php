<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_replace_equipment_photo_when_editing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'PHOTO-01', 'nama_alat' => 'Alat Foto', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'kondisi' => 'Baik', 'qr_code' => 'qr-photo-01', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($admin)->post(route('admin.equipment.update', $equipment), [
            '_method' => 'PUT',
            'kode_alat' => $equipment->kode_alat,
            'nama_alat' => $equipment->nama_alat,
            'id_kategori' => $category->id_kategori,
            'stok_total' => 1,
            'kondisi' => 'Baik',
            'status_alat' => 'Tersedia',
            'foto' => UploadedFile::fake()->create('alat-baru.jpg', 100, 'image/jpeg'),
        ])->assertRedirect();

        $photo = $equipment->fresh()->foto;
        $this->assertNotNull($photo);
        Storage::disk('public')->assertExists($photo);
    }

    public function test_missing_equipment_photo_uses_fallback_in_admin_and_catalog(): void
    {
        Storage::fake('public');
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'PHOTO-MISSING', 'nama_alat' => 'Foto Hilang', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'kondisi' => 'Baik', 'foto' => 'alat/missing.jpg',
            'qr_code' => 'qr-photo-missing', 'status_alat' => 'Tersedia',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($admin)->get(route('admin.equipment'))
            ->assertOk()
            ->assertSee('equipment-photo-placeholder');
        $this->actingAs($student)->get(route('catalog'))
            ->assertOk()
            ->assertSee('equipment-photo-placeholder');
        $this->assertDatabaseHas('alat', ['id_alat' => $equipment->id_alat, 'foto' => 'alat/missing.jpg']);
    }

    public function test_loan_history_uses_the_uploaded_equipment_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('alat/router.jpg', 'photo');
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'PHOTO-HISTORY', 'nama_alat' => 'Router Foto', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 0, 'kondisi' => 'Baik', 'foto' => 'alat/router.jpg',
            'qr_code' => 'qr-photo-history', 'status_alat' => 'Dipinjam',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Uji foto riwayat',
            'status' => 'Approved',
        ]);
        $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);

        $this->actingAs($student)->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('/storage/alat/router.jpg')
            ->assertSee('onerror="this.hidden=true;this.nextElementSibling.hidden=false"', false);
    }

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
