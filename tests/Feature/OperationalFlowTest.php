<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPeminjaman;
use App\Models\ItemUnit;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_cancel_only_their_own_pending_request(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $otherStudent = User::factory()->create(['role' => 'siswa']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Pengajuan yang dibatalkan',
            'status' => 'Pending',
        ]);
        $otherLoan = Peminjaman::create([
            'id_user' => $otherStudent->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Pengajuan milik siswa lain',
            'status' => 'Pending',
        ]);

        $this->actingAs($student)->post(route('loans.cancel', $loan))->assertRedirect();
        $this->assertSame('Cancelled', $loan->fresh()->status);

        $this->actingAs($student)->post(route('loans.cancel', $otherLoan))
            ->assertSessionHasErrors('status');
        $this->assertSame('Pending', $otherLoan->fresh()->status);
    }

    public function test_admin_approves_all_requests_and_technician_receives_unit_handover_tasks(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Operasional']);
        $legacyEquipment = Alat::create([
            'kode_alat' => 'LEGACY-01', 'nama_alat' => 'Alat Legacy', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'kondisi' => 'Baik', 'qr_code' => 'legacy-qr', 'status_alat' => 'Tersedia',
        ]);
        $unitEquipment = Alat::create([
            'kode_alat' => 'UNIT-01', 'nama_alat' => 'Alat Unit', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'kondisi' => 'Baik', 'qr_code' => 'unit-qr', 'status_alat' => 'Tersedia',
        ]);
        ItemUnit::create([
            'alat_id' => $unitEquipment->id_alat, 'asset_id' => 'UNIT-01-A', 'qr_code' => 'unit-asset-qr',
            'condition' => 'Baik', 'status' => 'Tersedia',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        foreach ([$legacyEquipment, $unitEquipment] as $equipment) {
            $loan = Peminjaman::create([
                'id_user' => $student->id_user,
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'keperluan' => 'Uji antrean operasional',
                'status' => 'Pending',
            ]);
            DetailPeminjaman::create(['id_peminjaman' => $loan->id_peminjaman, 'id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($admin)->get(route('admin.approvals'))
            ->assertOk()->assertSee('Alat Legacy')->assertSee('Alat Unit');
        $unitLoan = Peminjaman::whereHas('detail', fn ($query) => $query->where('id_alat', $unitEquipment->id_alat))->firstOrFail();
        $this->actingAs($admin)->post(route('admin.approvals.approve', $unitLoan))->assertRedirect();
        $this->assertSame('Approved', $unitLoan->fresh()->status);
        $this->assertDatabaseHas('detail_peminjaman_item_unit', [
            'id_detail' => $unitLoan->detail->first()->id_detail,
            'item_unit_id' => $unitEquipment->itemUnits()->value('item_unit_id'),
            'assigned_by' => $admin->id_user,
        ]);
        $this->assertDatabaseHas('item_units', ['alat_id' => $unitEquipment->id_alat, 'status' => 'Dipinjam']);
        $this->actingAs($technician)->get(route('technician.handovers'))
            ->assertOk()->assertSee('Alat Unit')->assertDontSee('Alat Legacy');
        $this->get('/teknisi/verifikasi')->assertNotFound();
    }

    public function test_unit_handover_records_the_exact_asset_id(): void
    {
        Storage::fake('public');
        $category = KategoriAlat::create(['nama_kategori' => 'Unit']);
        $equipment = Alat::create([
            'kode_alat' => 'UNIT-02', 'nama_alat' => 'Alat Berunit', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 0, 'kondisi' => 'Baik', 'qr_code' => 'unit-02-qr', 'status_alat' => 'Dipinjam',
        ]);
        $unit = ItemUnit::create([
            'alat_id' => $equipment->id_alat, 'asset_id' => 'UNIT-02-A', 'qr_code' => 'unit-02-asset-qr',
            'condition' => 'Baik', 'status' => 'Dipinjam',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $technician = User::factory()->create(['role' => 'teknisi']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Uji Asset ID',
            'status' => 'Approved',
        ]);
        $detail = DetailPeminjaman::create(['id_peminjaman' => $loan->id_peminjaman, 'id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        $detail->itemUnits()->attach($unit->item_unit_id, ['assigned_by' => $technician->id_user]);

        $this->actingAs($technician)->post(route('technician.handovers.store', $loan), [
            'id_detail' => $detail->id_detail,
            'item_unit_ids' => [$unit->item_unit_id],
            'jumlah' => 1,
            'kondisi_alat' => 'Baik',
            'foto_bukti' => UploadedFile::fake()->create('handover.jpg', 10, 'image/jpeg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('detail_penyerahan', [
            'id_detail' => $detail->id_detail,
            'item_unit_id' => $unit->item_unit_id,
            'jumlah' => 1,
        ]);
    }
}
