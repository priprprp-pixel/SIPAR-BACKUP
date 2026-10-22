<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Exports\LoansExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Peminjaman::with(['user', 'detail.alat', 'pengembalian'])->latest('tanggal_pengajuan');
        if ($request->filled('dari')) {
            $query->whereDate('tanggal_pengajuan', '>=', $request->date('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tanggal_pengajuan', '<=', $request->date('sampai'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Pending', 'Approved', 'Rejected', 'Selesai'], true)) {
            $query->where('status', $request->input('status'));
        }
        $summaryLoans = (clone $query)->with(['detail.pengembalianDetail', 'pengembalian'])->get();
        $loans = $query->with(['detail.pengembalianDetail'])->paginate(20)->withQueryString();
        $totalFine = $summaryLoans->sum(function (Peminjaman $loan): float {
            $detailFine = $loan->detail->flatMap(fn ($detail) => $detail->pengembalianDetail)->sum('denda');

            return (float) ($detailFine ?: ($loan->pengembalian?->denda ?? 0));
        });

        return view('admin.reports', [
            'loans' => $loans,
            'approvedCount' => $summaryLoans->whereIn('status', ['Approved', 'Selesai'])->count(),
            'pendingCount' => $summaryLoans->where('status', 'Pending')->count(),
            'totalFine' => $totalFine,
        ]);
    }

    public function excel(Request $request)
    {
        return Excel::download(
            new LoansExport($request->only(['dari', 'sampai', 'status'])),
            'sipar-laporan-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function pdf(Request $request): Response
    {
        $query = Peminjaman::with(['user', 'detail.alat', 'detail.pengembalianDetail', 'pengembalian'])->latest('tanggal_pengajuan');
        if ($request->filled('dari')) {
            $query->whereDate('tanggal_pengajuan', '>=', $request->date('dari'));
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tanggal_pengajuan', '<=', $request->date('sampai'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Pending', 'Approved', 'Rejected', 'Selesai'], true)) {
            $query->where('status', $request->input('status'));
        }

        $rows = $query->get()->map(function (Peminjaman $loan): array {
            $equipment = $loan->detail
                ->map(function ($detail): ?string {
                    if (! $detail->alat) {
                        return null;
                    }

                    return $detail->alat->nama_alat.' x '.$detail->jumlah;
                })
                ->filter()
                ->implode(', ');

            $detailReturns = $loan->detail->flatMap(fn ($detail) => $detail->pengembalianDetail);
            $latestReturn = $detailReturns->max('tanggal_kembali_aktual') ?? $loan->pengembalian?->tanggal_kembali_aktual;
            $fine = $detailReturns->sum('denda') ?: ($loan->pengembalian?->denda ?? 0);

            return [
                'id' => 'PJM-'.str_pad((string) $loan->id_peminjaman, 5, '0', STR_PAD_LEFT),
                'nama' => $loan->user?->nama ?? '-',
                'nis_nip' => $loan->user?->nis_nip ?? '-',
                'alat' => $equipment !== '' ? $equipment : '-',
                'tanggal_pinjam' => $loan->tanggal_pinjam?->format('d/m/Y') ?? '-',
                'tanggal_kembali_rencana' => $loan->tanggal_kembali_rencana?->format('d/m/Y') ?? '-',
                'status' => $loan->status_label ?? '-',
                'tanggal_kembali_aktual' => $latestReturn?->format('d/m/Y H:i') ?? '-',
                'denda' => number_format((float) $fine, 0, ',', '.'),
            ];
        });

        return Pdf::loadView('admin.reports-pdf', [
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape')->download('sipar-laporan-'.now()->format('Ymd-His').'.pdf');
    }
}
