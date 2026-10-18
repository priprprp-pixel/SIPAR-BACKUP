<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_a_student_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'siswa', 'nis_nip' => '20260003']);

        $this->actingAs($admin)->get(route('admin.students'))
            ->assertOk()
            ->assertSee('data-bs-target="#editStudent-'.$student->id_user.'"', false)
            ->assertSee('id="editStudent-'.$student->id_user.'"', false)
            ->assertSee(route('admin.students.update', $student), false);

        $this->put(route('admin.students.update', $student), [
            'nama' => 'Nama Diperbarui',
            'nis_nip' => '20260003',
            'email' => $student->email,
            'password' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id_user' => $student->id_user, 'nama' => 'Nama Diperbarui']);
    }
}
