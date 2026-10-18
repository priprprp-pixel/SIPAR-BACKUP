<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        Alat::create([
            'kode_alat' => 'LAN-001', 'nama_alat' => 'LAN Tester', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'kondisi' => 'Baik', 'qr_code' => 'qr-admin-test', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($admin);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('data-bs-target="#mobileNavigation"', false)
            ->assertSee('id="mobileNavigation"', false)
            ->assertSee(route('admin.categories'), false)
            ->assertSee(route('admin.students'), false)
            ->assertSee(route('admin.reports'), false);
        foreach ([
            route('dashboard'), route('admin.equipment'), route('admin.categories'), route('admin.students'),
            route('admin.approvals'), route('admin.returns'), route('admin.returns.lookup', 'qr-admin-test'),
            route('admin.reports'), route('admin.reports.xlsx'), route('profile'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_student_pages_render(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $category = KategoriAlat::create(['nama_kategori' => 'Jaringan']);
        Alat::create([
            'kode_alat' => 'LAN-002', 'nama_alat' => 'LAN Tester', 'id_kategori' => $category->id_kategori,
            'stok_total' => 2, 'stok_tersedia' => 2, 'kondisi' => 'Baik', 'qr_code' => 'qr-student-test', 'status_alat' => 'Tersedia',
        ]);

        $this->actingAs($student);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('data-bs-target="#mobileNavigation"', false)
            ->assertSee('id="mobileNavigation"', false)
            ->assertSee(route('catalog'), false)
            ->assertSee(route('loans.mine'), false)
            ->assertSee(route('profile'), false);
        foreach ([route('dashboard'), route('catalog'), route('loans.mine'), route('profile')] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson(route('catalog.lookup', 'qr-student-test'))->assertOk();
    }

    public function test_student_dashboard_summary_counts_all_their_loans(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        foreach (['Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Selesai', 'Rejected'] as $status) {
            Peminjaman::create([
                'id_user' => $student->id_user,
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
                'keperluan' => 'Dashboard summary test',
                'status' => $status,
            ]);
        }

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('myLoanCount', 8)
            ->assertViewHas('myPendingCount', 6)
            ->assertViewHas('myCompletedCount', 1);
    }

    public function test_student_catalog_uses_a_same_origin_qr_lookup_path(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'sipar-production.up.railway.app',
            'X-Forwarded-Port' => '443',
        ])->get(route('catalog'))
            ->assertOk()
            ->assertSee('\/katalog\/qr\/__TOKEN__', false)
            ->assertDontSee('https://sipar-production.up.railway.app/katalog/qr', false);
    }

    public function test_admin_return_scanner_uses_a_same_origin_qr_lookup_path(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'sipar-production.up.railway.app',
            'X-Forwarded-Port' => '443',
        ])->get(route('admin.returns'))
            ->assertOk()
            ->assertSee('\/admin\/pengembalian\/qr\/__TOKEN__', false)
            ->assertDontSee('https://sipar-production.up.railway.app/admin/pengembalian/qr', false);
    }
}
