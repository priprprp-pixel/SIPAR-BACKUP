<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_using_nis_and_reach_dashboard(): void
    {
        User::factory()->create([
            'nama' => 'Admin Toolman',
            'nis_nip' => 'ADMIN',
            'email' => 'admin@sipar.sch.id',
            'password' => 'ADMIN123',
            'role' => 'admin',
        ]);

        $this->post(route('login.store'), ['identity' => 'ADMIN', 'password' => 'ADMIN123', 'role' => 'admin'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee('Dashboard');
    }

    public function test_login_page_shows_student_registration_without_seed_instructions(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masukkan NIS atau email')
            ->assertSee('Lupa password? Hubungi Admin SIPAR.')
            ->assertSee('login-options d-flex justify-content-between align-items-center', false)
            ->assertSee('text-muted whitespace-nowrap', false)
            ->assertSee('Daftar sebagai siswa')
            ->assertSee('data-password-toggle', false)
            ->assertDontSee('Akun demo tersedia setelah menjalankan seeder.');
    }

    public function test_login_urls_follow_the_https_scheme_from_the_railway_proxy(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'sipar-production.up.railway.app',
            'X-Forwarded-Port' => '443',
        ])->get(route('login'))
            ->assertOk()
            ->assertSee('action="https://sipar-production.up.railway.app/login"', false)
            ->assertSee('href="https://sipar-production.up.railway.app/daftar"', false);
    }

    public function test_admin_login_error_uses_username_wording(): void
    {
        User::factory()->create(['role' => 'admin', 'nis_nip' => 'ADMIN-TEST']);

        $this->from(route('login'))->followingRedirects()->post(route('login.store'), [
            'identity' => 'ADMIN-TEST',
            'password' => 'wrong-password',
            'role' => 'admin',
        ])->assertOk()
            ->assertSee('Username atau password tidak cocok.')
            ->assertSee('id="login-role" value="admin"', false)
            ->assertSee('id="identity-label">Username</label>', false)
            ->assertDontSee('Kendala akses? Hubungi pengelola SIPAR.')
            ->assertSee('id="login-support"></span>', false);
    }

    public function test_seeder_updates_existing_admin_to_requested_credentials(): void
    {
        User::factory()->create([
            'nama' => 'Admin Lama',
            'nis_nip' => 'ADMIN001',
            'email' => 'admin@sipar.sch.id',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@sipar.sch.id')->firstOrFail();
        $this->assertSame('ADMIN', $admin->nis_nip);
        $this->assertTrue(Hash::check('ADMIN123', $admin->password));
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_login_rejects_a_role_that_does_not_match_the_account(): void
    {
        User::factory()->create(['role' => 'siswa', 'nis_nip' => '20260002']);

        $this->post(route('login.store'), ['identity' => '20260002', 'password' => 'password', 'role' => 'admin'])
            ->assertSessionHasErrors('identity');
    }

    public function test_registration_creates_student_and_not_admin(): void
    {
        $this->post(route('register.store'), [
            'nama' => 'Siswa Baru',
            'nis_nip' => '20260001',
            'email' => 'siswa.baru@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['nis_nip' => '20260001', 'role' => 'siswa']);
    }

    public function test_registration_explains_when_nis_or_email_is_already_registered(): void
    {
        User::factory()->create([
            'nis_nip' => '20260003',
            'email' => 'already.used@example.test',
        ]);

        $this->from(route('register'))->post(route('register.store'), [
            'nama' => 'Siswa Duplikat',
            'nis_nip' => '20260003',
            'email' => 'already.used@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['nis_nip', 'email']);

        $this->followingRedirects()->get(route('register'))
            ->assertOk()
            ->assertSee('NIS tersebut sudah terdaftar.')
            ->assertSee('Email tersebut sudah terdaftar.');
    }

    public function test_registration_explains_invalid_email_and_mismatched_password(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'nama' => 'Siswa Data Tidak Valid',
            'nis_nip' => '20260004',
            'email' => 'bukan-email',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->followingRedirects()->get(route('register'))
            ->assertOk()
            ->assertSee('Format email belum benar.')
            ->assertSee('Password dan ulangi password harus sama.');
    }
}
