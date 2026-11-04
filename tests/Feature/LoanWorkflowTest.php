<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyerahan;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_and_loan_submission_stay_protected(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'QR-PUBLIC', 'nama_alat' => 'Alat QR Publik', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'kondisi' => 'Baik', 'qr_code' => 'qr-public-test', 'status_alat' => 'Tersedia',
        ]);

        $this->get(route('catalog'))->assertRedirect(route('login'));
        $this->post(route('loans.create'), [])->assertRedirect(route('login'));
        $this->get(route('technician.returns.search', ['q' => $equipment->kode_alat]))->assertRedirect(route('login'));
        $this->assertSame(2, $equipment->fresh()->stok_tersedia);
    }

    public function test_student_can_submit_one_equipment_item_from_the_catalog_modal(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'QR-REQUEST', 'nama_alat' => 'Alat Pengajuan QR', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 3, 'kondisi' => 'Baik', 'qr_code' => 'qr-request-test', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)->post(route('loans.create'), [
            'id_alat' => $equipment->id_alat,
            'jumlah' => 1,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Praktik hasil scan QR',
        ])->assertRedirect(route('loans.mine'));

        $loan = Peminjaman::firstOrFail();
        $this->assertDatabaseHas('detail_peminjaman', [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_alat' => $equipment->id_alat,
            'jumlah' => 1,
        ]);
        $this->assertSame('Pending', $loan->status);
        $this->assertSame(3, $equipment->fresh()->stok_tersedia);
    }

    public function test_student_cannot_request_multiple_units_or_equipment_in_one_loan(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $firstEquipment = Alat::create([
            'kode_alat' => 'ONE-UNIT-A', 'nama_alat' => 'Alat Satu', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 3, 'kondisi' => 'Baik', 'qr_code' => 'one-unit-a', 'status_alat' => 'Tersedia',
        ]);
        $secondEquipment = Alat::create([
            'kode_alat' => 'ONE-UNIT-B', 'nama_alat' => 'Alat Dua', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 3, 'kondisi' => 'Baik', 'qr_code' => 'one-unit-b', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $request = [
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Praktik',
        ];

        $this->actingAs($student)->post(route('loans.create'), $request + [
            'items' => [['id_alat' => $firstEquipment->id_alat, 'jumlah' => 2]],
        ])->assertSessionHasErrors('items.0.jumlah');

        $this->post(route('loans.create'), $request + [
            'items' => [
                ['id_alat' => $firstEquipment->id_alat, 'jumlah' => 1],
                ['id_alat' => $secondEquipment->id_alat, 'jumlah' => 1],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, Peminjaman::count());
    }

    public function test_student_cannot_request_the_same_equipment_until_active_request_is_closed(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'ONE-ACTIVE', 'nama_alat' => 'Router Aktif', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 3, 'kondisi' => 'Baik', 'qr_code' => 'qr-one-active', 'status_alat' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Pengajuan aktif',
            'status' => 'Pending',
        ]);
        $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        $requestData = [
            'items' => [['id_alat' => $equipment->id_alat, 'jumlah' => 1]],
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Pengajuan kedua',
        ];

        $this->actingAs($student)->post(route('loans.create'), $requestData)
            ->assertSessionHasErrors('items');
        $this->assertSame(1, Peminjaman::count());

        $loan->update(['status' => 'Approved']);
        $this->post(route('loans.create'), $requestData)->assertSessionHasErrors('items');
        $loan->update(['status' => 'Selesai']);

        $this->post(route('loans.create'), $requestData)->assertRedirect(route('loans.mine'));
        $this->assertSame(2, Peminjaman::count());
    }

    public function test_admin_approval_and_late_return_update_stock_and_fine_for_existing_multi_item_loan(): void
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

        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => $now->toDateString(),
            'tanggal_kembali_rencana' => $now->copy()->addDay()->toDateString(),
            'keperluan' => 'Praktik jaringan kelas XI RPL',
            'status' => 'Pending',
        ]);
        $loan->detail()->createMany([
            ['id_alat' => $equipment->id_alat, 'jumlah' => 2],
            ['id_alat' => $secondEquipment->id_alat, 'jumlah' => 1],
        ]);
        $this->assertSame('Pending', $loan->status);
        $this->assertSame(3, $equipment->fresh()->stok_tersedia);
        $this->assertSame(2, $secondEquipment->fresh()->stok_tersedia);
        $this->assertCount(2, $loan->detail);

        $this->actingAs($admin)->post(route('admin.approvals.approve', $loan))->assertRedirect();
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $secondEquipment->fresh()->stok_tersedia);
        $this->assertSame('Approved', $loan->fresh()->status);
        $technician = User::factory()->create(['role' => 'teknisi']);
        foreach ($loan->detail as $detail) {
            DetailPenyerahan::create([
                'id_detail' => $detail->id_detail,
                'id_teknisi' => $technician->id_user,
                'jumlah' => $detail->jumlah,
                'tanggal_penyerahan' => now(),
                'kondisi_alat' => 'Baik',
            ]);
        }

        $returnedAt = $now->copy()->addDays(3)->setTime(11, 0);
        $this->travelTo($returnedAt->copy()->addMinute());

        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $loan->detail()->where('id_alat', $equipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'tanggal_kembali_aktual' => $returnedAt->toDateTimeString(),
            'kondisi_alat' => 'Rusak',
            'catatan' => 'Lengkap dan berfungsi.',
            'foto_bukti' => UploadedFile::fake()->create('return-1.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('technician.returns'));

        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $equipment->fresh()->stok_maintenance);
        $this->assertDatabaseHas('detail_pengembalian', [
            'id_detail' => $loan->detail()->where('id_alat', $equipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
        ]);
        $this->getJson(route('technician.returns.search', ['q' => $equipment->kode_alat]))
            ->assertOk()
            ->assertJsonPath('peminjaman.0.sisa', 1);
        $this->actingAs($technician)->get(route('technician.returns'))->assertOk();
        $this->actingAs($student)->get(route('loans.mine'))->assertOk();

        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $loan->detail()->where('id_alat', $equipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'tanggal_kembali_aktual' => $returnedAt->toDateTimeString(),
            'kondisi_alat' => 'Baik',
            'foto_bukti' => UploadedFile::fake()->create('return-2.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('technician.returns'));

        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertSame(2, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $equipment->fresh()->stok_maintenance);

        $this->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $loan->detail()->where('id_alat', $secondEquipment->id_alat)->value('id_detail'),
            'jumlah' => 1,
            'kode_alat' => $secondEquipment->kode_alat,
            'tanggal_kembali_aktual' => $returnedAt->toDateTimeString(),
            'kondisi_alat' => 'Baik',
            'catatan' => 'Router lengkap.',
            'foto_bukti' => UploadedFile::fake()->create('return-router.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('technician.returns'));

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
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes alat hilang',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        $equipment->update(['stok_tersedia' => 1, 'status_alat' => 'Tersedia']);
        $technician = User::factory()->create(['role' => 'teknisi']);
        DetailPenyerahan::create([
            'id_detail' => $detail->id_detail,
            'id_teknisi' => $technician->id_user,
            'jumlah' => 1,
            'tanggal_penyerahan' => now(),
            'kondisi_alat' => 'Baik',
        ]);

        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'kondisi_alat' => 'Hilang',
            'foto_bukti' => UploadedFile::fake()->create('lost.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('technician.returns'));

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
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes batas jumlah pengembalian',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        $technician = User::factory()->create(['role' => 'teknisi']);
        DetailPenyerahan::create([
            'id_detail' => $detail->id_detail,
            'id_teknisi' => $technician->id_user,
            'jumlah' => 1,
            'tanggal_penyerahan' => now(),
            'kondisi_alat' => 'Baik',
        ]);

        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 2,
            'kode_alat' => $equipment->kode_alat,
            'kondisi_alat' => 'Baik',
            'foto_bukti' => UploadedFile::fake()->create('over-return.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(0, $detail->pengembalianDetail()->count());
    }

    public function test_partial_technician_return_restores_stock_without_admin_approval(): void
    {
        Storage::fake('public');
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'RETURN-PARTIAL', 'nama_alat' => 'Alat Bertahap', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 0, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-return-partial', 'status_alat' => 'Dipinjam',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $technician = User::factory()->create(['role' => 'teknisi']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Tes pengembalian bertahap',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 2]);
        DetailPenyerahan::create([
            'id_detail' => $detail->id_detail,
            'id_teknisi' => $technician->id_user,
            'jumlah' => 2,
            'tanggal_penyerahan' => now(),
            'kondisi_alat' => 'Baik',
        ]);

        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'kondisi_alat' => 'Baik',
            'foto_bukti' => UploadedFile::fake()->create('return-partial.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('technician.returns'));

        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame(1, $detail->pengembalianDetail()->sum('jumlah'));
        $this->assertSame('Approved', $loan->fresh()->status);
        $this->assertDatabaseMissing('pengembalian', ['id_peminjaman' => $loan->id_peminjaman]);
    }

    public function test_return_search_distinguishes_unknown_and_unborrowed_equipment(): void
    {
        $technician = User::factory()->create(['role' => 'teknisi']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'QR-NO-LOAN', 'nama_alat' => 'Alat Belum Dipinjam', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'stok_maintenance' => 0, 'stok_rusak' => 0,
            'kondisi' => 'Baik', 'qr_code' => 'qr-no-loan', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($technician)->getJson(route('technician.returns.search', ['q' => 'alat-tidak-dikenal']))
            ->assertNotFound()
            ->assertJsonPath('message', 'Alat tidak ditemukan. Gunakan kode atau nama alat yang benar.');

        $this->getJson(route('technician.returns.search', ['q' => $equipment->kode_alat]))
            ->assertOk()
            ->assertJsonPath('alat', 'Alat Belum Dipinjam')
            ->assertJsonPath('status', 'Tersedia')
            ->assertJsonPath('stok_tersedia', 1)
            ->assertJsonPath('stok_total', 1)
            ->assertJsonPath('peminjaman', []);

        $this->get(route('technician.returns'))
            ->assertOk()
            ->assertSee('Alat ditemukan, tetapi tidak ada sisa transaksi peminjaman aktif', false);
    }

    public function test_student_cannot_open_admin_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'siswa']))
            ->get(route('admin.equipment'))
            ->assertForbidden();
    }
}
