<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyerahan;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_pending_returns_are_applied_by_the_migration(): void
    {
        $category = KategoriAlat::create(['nama_kategori' => 'Kategori Migrasi Pengembalian']);
        $equipment = Alat::create([
            'kode_alat' => 'RETURN-MIGRATION', 'nama_alat' => 'Alat Migrasi', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 0, 'kondisi' => 'Baik', 'qr_code' => 'qr-return-migration',
            'status_alat' => 'Dipinjam',
        ]);
        $student = User::factory()->create(['role' => 'siswa']);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Migrasi pengembalian lama',
            'status' => 'Approved',
        ]);
        $detail = $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);
        DB::table('detail_pengembalian')->insert([
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'tanggal_kembali_aktual' => now(),
            'kondisi_alat' => 'Baik',
            'denda' => 0,
            'status_verifikasi' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('database/migrations/2026_11_04_000000_apply_legacy_pending_returns.php');
        $migration->up();

        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertSame('Selesai', $loan->fresh()->status);
        $this->assertDatabaseHas('detail_pengembalian', [
            'id_detail' => $detail->id_detail,
            'status_verifikasi' => 'Approved',
        ]);
        $this->assertDatabaseHas('pengembalian', ['id_peminjaman' => $loan->id_peminjaman]);
    }

    public function test_technician_can_record_handover_but_cannot_access_admin_data(): void
    {
        Storage::fake('public');
        $technician = User::factory()->create(['role' => 'teknisi', 'nama' => 'Teknisi Uji']);
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

        $this->actingAs($technician)->get(route('technician.handovers'))
            ->assertOk()
            ->assertSee('capture="environment"', false);
        $handoverData = [
            'id_detail' => $detail->id_detail, 'jumlah' => 1, 'kondisi_alat' => 'Baik',
        ];
        $this->actingAs($technician)
            ->post(route('technician.handovers.store', $loan), $handoverData)
            ->assertSessionHasErrors('foto_bukti');
        $this->actingAs($technician)->post(route('technician.handovers.store', $loan), $handoverData + [
            'foto_bukti' => UploadedFile::fake()->create('penyerahan.jpg', 100, 'image/jpeg'),
        ])->assertRedirect();
        $this->assertDatabaseHas('detail_penyerahan', ['id_detail' => $detail->id_detail, 'id_teknisi' => $technician->id_user]);
        $this->actingAs($technician)->get(route('technician.returns'))
            ->assertOk()
            ->assertSee('capture="environment"', false);
        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'kondisi_alat' => 'Baik',
        ])->assertSessionHasErrors('foto_bukti');
        $this->actingAs($technician)->post(route('technician.returns.store'), [
            'id_peminjaman' => $loan->id_peminjaman,
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'kode_alat' => $equipment->kode_alat,
            'kondisi_alat' => 'Baik',
            'foto_bukti' => UploadedFile::fake()->create('pengembalian.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('technician.returns'));
        $this->assertSame(1, $equipment->fresh()->stok_tersedia);
        $this->assertDatabaseHas('detail_pengembalian', [
            'id_detail' => $detail->id_detail,
            'status_verifikasi' => 'Approved',
            'id_teknisi' => $technician->id_user,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Verifikasi Pengembalian');
        $this->get('/admin/pengembalian/verifikasi')->assertNotFound();
        $this->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Riwayat pengembalian item')
            ->assertSee('Laptop Teknisi')
            ->assertSee('Teknisi Uji')
            ->assertSee('Lihat foto');
        $this->assertSame('Selesai', $loan->fresh()->status);
        $this->actingAs($admin)->get('/admin/pengembalian')->assertNotFound();
        $this->actingAs($technician)->get(route('admin.equipment'))->assertForbidden();
    }
}
