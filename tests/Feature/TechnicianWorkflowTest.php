<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyerahan;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_record_handover_but_cannot_access_admin_data(): void
    {
        $technician = User::factory()->create(['role' => 'teknisi']);
        $student = User::factory()->create(['role' => 'siswa']);
        $category = KategoriAlat::create(['nama_kategori' => 'Kategori Uji Teknisi']);
        $equipment = Alat::create([
            'kode_alat' => 'KOM-TECH', 'nama_alat' => 'Laptop Teknisi', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 0, 'kondisi' => 'Baik', 'qr_code' => 'qr-tech',
            'status_alat' => 'Dipinjam',
        ]);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user, 'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(5)->toDateString(), 'keperluan' => 'Praktik',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);

        $this->actingAs($technician)->get(route('technician.handovers'))->assertOk();
        $this->actingAs($technician)->post(route('technician.handovers.store', $loan), [
            'id_detail' => $detail->id_detail, 'jumlah' => 1, 'kondisi_alat' => 'Baik',
        ])->assertRedirect();
        $this->assertDatabaseHas('detail_penyerahan', ['id_detail' => $detail->id_detail, 'id_teknisi' => $technician->id_user]);
        $this->actingAs($technician)->get(route('admin.equipment'))->assertForbidden();
    }
}
