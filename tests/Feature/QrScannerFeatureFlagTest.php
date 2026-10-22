<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrScannerFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_scanner_replaces_student_scanner_with_manual_equipment_code_lookup(): void
    {
        config(['app.enable_qr_scanner' => false]);
        $student = User::factory()->create(['role' => 'siswa']);
        $equipment = $this->createEquipment();

        $this->actingAs($student)->get(route('catalog'))
            ->assertOk()
            ->assertSee('id="manual-equipment-lookup"', false)
            ->assertSee('id="manual-equipment-code"', false)
            ->assertDontSee('student-reader')
            ->assertDontSee('html5-qrcode');

        $this->getJson(route('catalog.lookup', $equipment->kode_alat))
            ->assertOk()
            ->assertJsonPath('id_alat', $equipment->id_alat)
            ->assertJsonPath('kode', $equipment->kode_alat);
    }

    public function test_admin_can_use_qr_scanner_and_manual_code_lookup_together(): void
    {
        config(['app.enable_qr_scanner' => false]);
        $admin = User::factory()->create(['role' => 'admin']);
        $equipment = $this->createEquipment();

        $this->actingAs($admin)->get(route('admin.returns'))
            ->assertOk()
            ->assertSee('Masukkan kode alat, contoh: LAN-001')
            ->assertSee('name="kode_alat"', false)
            ->assertSee('name="qr_code"', false)
            ->assertSee('id="return-reader"', false)
            ->assertSee('html5-qrcode')
            ->assertSee('Scan QR alat');

        $this->getJson(route('admin.returns.lookup', $equipment->kode_alat))
            ->assertOk()
            ->assertJsonPath('alat', $equipment->nama_alat)
            ->assertJsonPath('kode_alat', $equipment->kode_alat)
            ->assertJsonPath('qr_code', $equipment->qr_code);

        $this->getJson(route('admin.returns.lookup', $equipment->qr_code))
            ->assertOk()
            ->assertJsonPath('alat', $equipment->nama_alat)
            ->assertJsonPath('kode_alat', $equipment->kode_alat)
            ->assertJsonPath('qr_code', $equipment->qr_code);

        config(['app.enable_qr_scanner' => true]);
        $this->get(route('admin.returns'))
            ->assertOk()
            ->assertSee('Masukkan kode alat, contoh: LAN-001')
            ->assertSee('name="kode_alat"', false)
            ->assertSee('name="qr_code"', false)
            ->assertSee('id="return-reader"', false)
            ->assertSee('Scan QR alat');
    }

    public function test_manual_equipment_code_can_submit_return_while_qr_scanner_is_enabled(): void
    {
        config(['app.enable_qr_scanner' => true]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $equipment = $this->createEquipment();
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes pengembalian kode alat',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);

        $this->actingAs($admin)->getJson(route('admin.returns.lookup', $equipment->kode_alat))
            ->assertOk()
            ->assertJsonPath('alat', $equipment->nama_alat)
            ->assertJsonPath('peminjaman.0.id', $loan->id_peminjaman)
            ->assertJsonPath('peminjaman.0.id_detail', $detail->id_detail)
            ->assertJsonPath('peminjaman.0.sisa', 1);

        $this->actingAs($admin)->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'kondisi_alat' => 'Baik',
        ])->assertRedirect(route('admin.returns'));

        $this->assertDatabaseHas('detail_pengembalian', [
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
        ]);
    }

    public function test_qr_code_can_submit_return_while_manual_lookup_is_available(): void
    {
        config(['app.enable_qr_scanner' => true]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $equipment = $this->createEquipment();
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes pengembalian QR hybrid',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);

        $this->actingAs($admin)->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'qr_code' => $equipment->qr_code,
            'kondisi_alat' => 'Baik',
        ])->assertRedirect(route('admin.returns'));

        $this->assertDatabaseHas('detail_pengembalian', [
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
        ]);
    }

    private function createEquipment(): Alat
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);

        return Alat::create([
            'kode_alat' => 'LAN-001',
            'nama_alat' => 'LAN Tester',
            'id_kategori' => $category->id_kategori,
            'stok_total' => 2,
            'stok_tersedia' => 1,
            'kondisi' => 'Baik',
            'qr_code' => 'qr-lan-001',
            'status_alat' => 'Dipinjam',
        ]);
    }
}
