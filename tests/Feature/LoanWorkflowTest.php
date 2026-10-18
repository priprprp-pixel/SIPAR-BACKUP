<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_request_admin_approval_and_late_return_update_stock_and_fine(): void
    {
        $now = Carbon::now()->addDays(3)->setTime(12, 0);
        $this->travelTo($now);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'LAN-TEST', 'nama_alat' => 'LAN Tester', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 3, 'kondisi' => 'Baik', 'qr_code' => 'qr-lan-test', 'status_alat' => 'Tersedia',
        ]);
        $secondEquipment = Alat::create([
            'kode_alat' => 'ROUTER-TEST', 'nama_alat' => 'Router Test', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'kondisi' => 'Baik', 'qr_code' => 'qr-router-test', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($student)->post(route('loans.create'), [
            'items' => [
                ['id_alat' => $equipment->id_alat, 'jumlah' => 2],
                ['id_alat' => $secondEquipment->id_alat, 'jumlah' => 1],
            ],
            'tanggal_pinjam' => $now->toDateString(),
            'tanggal_kembali_rencana' => $now->copy()->addDay()->toDateString(),
            'keperluan' => 'Praktik jaringan kelas XI RPL',
        ])->assertRedirect(route('loans.mine'));

        $loan = Peminjaman::firstOrFail();
        $this->assertSame('Pending', $loan->status);
        $this->assertSame(3, $equipment->fresh()->stok_tersedia);
        $this->assertSame(2, $secondEquipment->fresh()->stok_tersedia);
        $this->assertCount(2, $loan->detail);

        $this->actingAs($admin)->post(route('admin.approvals.approve', $loan))->assertRedirect();
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $secondEquipment->fresh()->stok_tersedia);
        $this->assertSame('Approved', $loan->fresh()->status);

        $returnedAt = $now->copy()->addDays(3)->setTime(11, 0);
        $this->travelTo($returnedAt->copy()->addMinute());

        $this->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $loan->detail()->where('id_alat', $equipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
            'qr_code' => $equipment->qr_code,
            'tanggal_kembali_aktual' => $returnedAt->toDateTimeString(),
            'kondisi_alat' => 'Rusak',
            'catatan' => 'Lengkap dan berfungsi.',
        ])->assertRedirect(route('admin.returns'));

        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $equipment->fresh()->stok_maintenance);
        $this->assertDatabaseHas('detail_pengembalian', [
            'id_detail' => $loan->detail()->where('id_alat', $equipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
        ]);
        $this->getJson(route('admin.returns.lookup', $equipment->qr_code))
            ->assertOk()
            ->assertJsonPath('peminjaman.0.sisa', 1);
        $this->actingAs($admin)->get(route('admin.returns'))->assertOk();
        $this->actingAs($student)->get(route('loans.mine'))->assertOk();

        $this->actingAs($admin);
        $this->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $loan->detail()->where('id_alat', $equipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
            'qr_code' => $equipment->qr_code,
            'tanggal_kembali_aktual' => $returnedAt->toDateTimeString(),
            'kondisi_alat' => 'Baik',
        ])->assertRedirect(route('admin.returns'));

        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertSame(2, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $equipment->fresh()->stok_maintenance);

        $this->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $loan->detail()->where('id_alat', $secondEquipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
            'qr_code' => $secondEquipment->qr_code,
            'tanggal_kembali_aktual' => $returnedAt->toDateTimeString(),
            'kondisi_alat' => 'Baik',
            'catatan' => 'Router lengkap.',
        ])->assertRedirect(route('admin.returns'));

        $this->assertSame(2, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $equipment->fresh()->stok_maintenance);
        $this->assertSame(2, $secondEquipment->fresh()->stok_tersedia);
        $this->assertSame('Selesai', $loan->fresh()->status);
        $this->assertDatabaseHas('pengembalian', ['id_peminjaman' => $loan->id_peminjaman, 'denda' => 10000]);
        $this->assertDatabaseHas('pengembalian', ['id_peminjaman' => $loan->id_peminjaman, 'kondisi_alat' => 'Rusak']);
    }

    public function test_lost_return_reduces_total_stock_without_inflating_available_stock(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'SWITCH-LOST', 'nama_alat' => 'Switch Test', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'stok_maintenance' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-switch-lost', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes alat hilang',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        $equipment->update(['stok_tersedia' => 1, 'status_alat' => 'Tersedia']);

        $this->actingAs($admin)->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'qr_code' => $equipment->qr_code,
            'kondisi_alat' => 'Hilang',
        ])->assertRedirect(route('admin.returns'));

        $this->assertSame(1, $equipment->fresh()->stok_total);
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(0, $equipment->fresh()->stok_maintenance);
        $this->assertSame('Selesai', $loan->fresh()->status);
        $this->assertDatabaseHas('pengembalian', ['id_peminjaman' => $loan->id_peminjaman, 'kondisi_alat' => 'Hilang']);
    }

    public function test_multi_item_approval_rolls_back_stock_when_one_item_is_no_longer_available(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $firstEquipment = Alat::create([
            'kode_alat' => 'ATOMIC-1', 'nama_alat' => 'Alat Atomic Satu', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-atomic-1', 'status_alat' => 'Tersedia',
        ]);
        $secondEquipment = Alat::create([
            'kode_alat' => 'ATOMIC-2', 'nama_alat' => 'Alat Atomic Dua', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 0, 'stok_maintenance' => 1, 'stok_rusak' => 0,
            'kondisi' => 'Maintenance', 'qr_code' => 'qr-atomic-2', 'status_alat' => 'Maintenance',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes approval atomik',
            'status' => 'Pending',
        ]);
        $loan->detail()->createMany([
            ['id_alat' => $firstEquipment->id_alat, 'jumlah' => 1],
            ['id_alat' => $secondEquipment->id_alat, 'jumlah' => 1],
        ]);

        $this->actingAs($admin)->post(route('admin.approvals.approve', $loan))
            ->assertSessionHasErrors('stok');

        $this->assertSame('Pending', $loan->fresh()->status);
        $this->assertSame(2, $firstEquipment->fresh()->stok_tersedia);
        $this->assertSame(1, $secondEquipment->fresh()->stok_maintenance);
        $this->assertSame(0, $secondEquipment->fresh()->stok_tersedia);
    }

    public function test_rejecting_a_pending_multi_item_loan_does_not_change_stock(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'REJECT-STOCK', 'nama_alat' => 'Alat Ditolak', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 3, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-reject-stock', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes penolakan',
            'status' => 'Pending',
        ]);
        $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 2]);

        $this->actingAs($admin)->post(route('admin.approvals.reject', $loan), [
            'catatan_admin' => 'Jadwal penggunaan alat tidak tersedia.',
        ])->assertRedirect();

        $this->assertSame('Rejected', $loan->fresh()->status);
        $this->assertSame(3, $equipment->fresh()->stok_tersedia);
        $this->assertSame(0, $equipment->fresh()->stok_maintenance);
        $this->assertSame(0, $equipment->fresh()->stok_rusak);
        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $loan->id_peminjaman,
            'catatan_admin' => 'Jadwal penggunaan alat tidak tersedia.',
        ]);
    }

    public function test_return_cannot_exceed_the_remaining_quantity(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'RETURN-LIMIT', 'nama_alat' => 'Alat Batas Kembali', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 1, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-return-limit', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $admin = User::factory()->create(['role' => 'admin']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes batas jumlah pengembalian',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);

        $this->actingAs($admin)->post(route('admin.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 2,
            'qr_code' => $equipment->qr_code,
            'kondisi_alat' => 'Baik',
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(0, $detail->pengembalianDetail()->count());
    }

    public function test_return_qr_lookup_distinguishes_unknown_and_unborrowed_equipment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        Alat::create([
            'kode_alat' => 'QR-NO-LOAN', 'nama_alat' => 'Alat Belum Dipinjam', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-no-loan', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($admin)->getJson(route('admin.returns.lookup', 'qr-tidak-dikenal'))
            ->assertNotFound()
            ->assertJsonPath('message', 'QR alat tidak ditemukan.');

        $this->getJson(route('admin.returns.lookup', 'qr-no-loan'))
            ->assertOk()
            ->assertJsonPath('alat', 'Alat Belum Dipinjam')
            ->assertJsonPath('status', 'Tersedia')
            ->assertJsonPath('stok_tersedia', 1)
            ->assertJsonPath('stok_total', 1)
            ->assertJsonPath('peminjaman', []);

        $this->get(route('admin.returns'))
            ->assertOk()
            ->assertSee('QR alat dikenali, tetapi tidak ada sisa transaksi peminjaman aktif', false);
    }

    public function test_student_cannot_open_admin_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get(route('admin.equipment'))
            ->assertForbidden();
    }
}
