<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_search_finds_equipment_students_and_loans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'siswa',
            'nama' => 'Rani Search',
            'nis_nip' => 'NIS-SEARCH-1',
        ]);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        $equipment = Alat::create([
            'kode_alat' => 'SEARCH-LAN', 'nama_alat' => 'LAN Search Tool', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'kondisi' => 'Baik', 'qr_code' => 'qr-search-lan', 'status_alat' => 'Tersedia',
        ]);
        $loan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Search feature test',
            'status' => 'Pending',
        ]);
        $loan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 1]);

        $this->actingAs($admin)->get(route('search', ['q' => 'SEARCH']))
            ->assertOk()
            ->assertViewHas('equipment', fn ($items) => $items->contains('id_alat', $equipment->id_alat))
            ->assertViewHas('students', fn ($students) => $students->contains('id_user', $student->id_user))
            ->assertViewHas('loans', fn ($loans) => $loans->contains('id_peminjaman', $loan->id_peminjaman));
    }

    public function test_student_search_only_returns_their_own_loans(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $otherStudent = User::factory()->create(['role' => 'siswa']);
        $ownLoan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Student search fixture',
            'status' => 'Pending',
        ]);
        $otherLoan = Peminjaman::create([
            'id_user' => $otherStudent->id_user,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
            'keperluan' => 'Student search fixture',
            'status' => 'Pending',
        ]);

        $this->actingAs($student)->get(route('search', ['q' => 'Student search fixture']))
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->contains('id_peminjaman', $ownLoan->id_peminjaman)
                && ! $loans->contains('id_peminjaman', $otherLoan->id_peminjaman))
            ->assertViewHas('students', fn ($students) => $students->isEmpty());
    }

    public function test_global_search_suggestions_return_equipment_categories_and_admin_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'siswa',
            'nama' => 'Autocomplete Student',
            'nis_nip' => 'AUTO-001',
        ]);
        $category = KategoriAlat::create(['nama_kategori' => 'Autocategory']);
        Alat::create([
            'kode_alat' => 'AUTO-TOOL', 'nama_alat' => 'Autocomplete Tool', 'id_kategori' => $category->id_kategori,
            'stok_total' => 1, 'stok_tersedia' => 1, 'kondisi' => 'Baik', 'qr_code' => 'qr-auto-tool', 'status_alat' => 'Tersedia',
        ]);

        $response = $this->actingAs($admin)->getJson(route('search.suggestions', ['q' => 'AUTO']));

        $response->assertOk()
            ->assertJsonPath('results.0.type', 'Alat')
            ->assertJsonFragment(['type' => 'Kategori', 'title' => 'Autocategory'])
            ->assertJsonFragment(['type' => 'Siswa', 'title' => 'Autocomplete Student']);
    }
}
