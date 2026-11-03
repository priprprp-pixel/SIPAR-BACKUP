<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class StudentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_student_excel_without_passwords(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'siswa', 'nama' => 'Siswa Backup', 'nis_nip' => 'BACKUP-001',
            'email' => 'backup@example.test',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.xlsx'));

        $response->assertOk()->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $temporaryFile = tempnam(sys_get_temp_dir(), 'sipar-students-');
        $temporaryPath = $temporaryFile.'.xlsx';
        try {
            $baseResponse = $response->baseResponse;
            if ($baseResponse instanceof BinaryFileResponse) {
                copy($baseResponse->getFile()->getPathname(), $temporaryPath);
            } elseif ($baseResponse instanceof StreamedResponse) {
                file_put_contents($temporaryPath, $response->streamedContent());
            } else {
                file_put_contents($temporaryPath, $baseResponse->getContent());
            }
            $sheet = IOFactory::load($temporaryPath)->getActiveSheet();
            $this->assertSame(['Nama', 'NIS', 'Email', 'Terdaftar'], $sheet->rangeToArray('A1:D1', null, true, false)[0]);
            $this->assertSame('Siswa Backup', $sheet->getCell('A2')->getValue());
            $this->assertSame('BACKUP-001', $sheet->getCell('B2')->getValue());
            $this->assertNull($sheet->getCell('E2')->getValue());
        } finally {
            @unlink($temporaryPath);
            @unlink($temporaryFile);
        }
    }

    public function test_admin_can_download_student_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.students.pdf'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
