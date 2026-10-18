<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_a_real_pdf_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
    }

    public function test_admin_can_download_a_valid_filtered_xlsx_workbook(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'siswa', 'nama' => 'Siswa XLSX', 'nis_nip' => 'XLSX-001']);
        $category = KategoriAlat::create(['nama_kategori' => 'Kategori XLSX']);
        $equipment = Alat::create([
            'kode_alat' => 'XLSX-001', 'nama_alat' => 'LAN Tester Export', 'id_kategori' => $category->id_kategori,
            'stok_total' => 3, 'stok_tersedia' => 2, 'kondisi' => 'Baik', 'qr_code' => 'qr-xlsx-export', 'status_alat' => 'Tersedia',
        ]);
        $approvedLoan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pengajuan' => '2026-10-15 10:00:00',
            'tanggal_pinjam' => '2026-10-15',
            'tanggal_kembali_rencana' => '2026-10-17',
            'keperluan' => 'Tes unduhan XLSX',
            'status' => 'Approved',
            'catatan_admin' => 'Catatan export',
        ]);
        $approvedLoan->detail()->create(['id_alat' => $equipment->id_alat, 'jumlah' => 2]);
        Pengembalian::create([
            'id_peminjaman' => $approvedLoan->id_peminjaman,
            'tanggal_kembali_aktual' => '2026-10-16 14:30:00',
            'kondisi_alat' => 'Baik',
            'denda' => 2500,
        ]);
        $pendingLoan = Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pengajuan' => '2026-10-15 11:00:00',
            'tanggal_pinjam' => '2026-10-15',
            'tanggal_kembali_rencana' => '2026-10-17',
            'keperluan' => 'Baris yang harus terfilter',
            'status' => 'Pending',
        ]);
        Peminjaman::create([
            'id_user' => $student->id_user,
            'tanggal_pengajuan' => '2026-09-30 10:00:00',
            'tanggal_pinjam' => '2026-09-30',
            'tanggal_kembali_rencana' => '2026-10-02',
            'keperluan' => 'Baris di luar rentang tanggal',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.xlsx', [
            'dari' => '2026-10-01',
            'sampai' => '2026-10-31',
            'status' => 'Approved',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertMatchesRegularExpression(
            '/attachment; filename="?sipar-laporan-\d{8}-\d{6}\.xlsx"?/i',
            $response->headers->get('content-disposition')
        );

        $temporaryFile = tempnam(sys_get_temp_dir(), 'sipar-xlsx-');
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

            $workbook = IOFactory::load($temporaryPath);
            $sheet = $workbook->getActiveSheet();

            $this->assertSame(2, $sheet->getHighestRow());
            $this->assertSame([
                'ID', 'Nama Siswa', 'NIS/NIP', 'Alat', 'Jumlah', 'Tanggal Pinjam',
                'Rencana Kembali', 'Status', 'Kembali Aktual', 'Denda (Rp)', 'Catatan',
            ], $sheet->rangeToArray('A1:K1', null, true, false)[0]);
            $this->assertSame('PJM-'.str_pad((string) $approvedLoan->id_peminjaman, 5, '0', STR_PAD_LEFT), $sheet->getCell('A2')->getValue());
            $this->assertSame('Siswa XLSX', $sheet->getCell('B2')->getValue());
            $this->assertSame('XLSX-001', $sheet->getCell('C2')->getValue());
            $this->assertSame('LAN Tester Export x 2', $sheet->getCell('D2')->getValue());
            $this->assertEquals(2, $sheet->getCell('E2')->getValue());
            $this->assertSame('2026-10-15', $sheet->getCell('F2')->getValue());
            $this->assertSame('Approved', $sheet->getCell('H2')->getValue());
            $this->assertSame('2026-10-16 14:30', $sheet->getCell('I2')->getValue());
            $this->assertEquals(2500, $sheet->getCell('J2')->getValue());
            $this->assertSame('Catatan export', $sheet->getCell('K2')->getValue());
            $this->assertNotSame($pendingLoan->id_peminjaman, $approvedLoan->id_peminjaman);
        } finally {
            @unlink($temporaryPath);
            @unlink($temporaryFile);
        }
    }

    public function test_report_summary_counts_all_filtered_rows_not_only_current_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'siswa']);
        $approvedLoan = null;

        for ($index = 0; $index < 21; $index++) {
            $loan = Peminjaman::create([
                'id_user' => $student->id_user,
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDay()->toDateString(),
                'keperluan' => 'Tes agregasi laporan',
                'status' => $index === 20 ? 'Approved' : 'Pending',
            ]);

            if ($index === 20) {
                $approvedLoan = $loan;
            }
        }

        Pengembalian::create([
            'id_peminjaman' => $approvedLoan->id_peminjaman,
            'tanggal_kembali_aktual' => now(),
            'kondisi_alat' => 'Baik',
            'denda' => 7500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 21)
            ->assertViewHas('approvedCount', 1)
            ->assertViewHas('pendingCount', 20)
            ->assertViewHas('totalFine', 7500.0);
    }
}
