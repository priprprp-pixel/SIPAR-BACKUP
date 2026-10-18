<?php

namespace App\Exports;

use App\Models\Peminjaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoansExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Siswa',
            'NIS/NIP',
            'Alat',
            'Jumlah',
            'Tanggal Pinjam',
            'Rencana Kembali',
            'Status',
            'Kembali Aktual',
            'Denda (Rp)',
            'Catatan',
        ];
    }

    public function collection(): Collection
    {
        return $this->query()->get()->map(function (Peminjaman $loan): array {
            $details = $loan->detail;
            $returns = $details->flatMap(fn ($detail) => $detail->pengembalianDetail);
            $latestReturn = $returns->max('tanggal_kembali_aktual') ?? $loan->pengembalian?->tanggal_kembali_aktual;
            $fine = $returns->sum('denda') ?: ($loan->pengembalian?->denda ?? 0);

            return [
                'PJM-'.str_pad((string) $loan->id_peminjaman, 5, '0', STR_PAD_LEFT),
                $loan->user?->nama ?? '-',
                $loan->user?->nis_nip ?? '-',
                $details->map(fn ($detail) => $detail->alat?->nama_alat.' x '.$detail->jumlah)->filter()->implode(', '),
                $details->sum('jumlah'),
                $loan->tanggal_pinjam?->format('Y-m-d') ?? '-',
                $loan->tanggal_kembali_rencana?->format('Y-m-d') ?? '-',
                $loan->status ?? '-',
                $latestReturn?->format('Y-m-d H:i') ?? '-',
                (float) $fine,
                $loan->catatan_admin ?? $loan->pengembalian?->catatan ?? '-',
            ];
        });
    }

    private function query(): Builder
    {
        $query = Peminjaman::query()
            ->with(['user', 'detail.alat', 'detail.pengembalianDetail', 'pengembalian'])
            ->latest('tanggal_pengajuan');

        if (! empty($this->filters['dari'])) {
            $query->whereDate('tanggal_pengajuan', '>=', $this->filters['dari']);
        }

        if (! empty($this->filters['sampai'])) {
            $query->whereDate('tanggal_pengajuan', '<=', $this->filters['sampai']);
        }

        if (in_array($this->filters['status'] ?? null, ['Pending', 'Approved', 'Rejected', 'Selesai'], true)) {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }
}
