<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->user()->role;
        $isAdmin = $role === 'admin';
        $isTeknisi = $role === 'teknisi';
        $loans = Peminjaman::with(['user', 'detail.alat'])->latest('tanggal_pengajuan');
        $approvedLoans = $isTeknisi
            ? Peminjaman::with(['detail.penyerahanDetail', 'detail.pengembalianDetail'])
                ->where('status', 'Approved')->get()
            : collect();

        if (! $isAdmin && ! $isTeknisi) {
            $loans->where('id_user', $request->user()->id_user);
        }
        $myLoans = Peminjaman::where('id_user', $request->user()->id_user);

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'isTeknisi' => $isTeknisi,
            'totalAlat' => Alat::count(),
            'tersedia' => Alat::where('status_alat', 'Tersedia')->sum('stok_tersedia'),
            'pending' => Peminjaman::where('status', 'Pending')->count(),
            'dipinjam' => Peminjaman::where('status', 'Approved')->count(),
            'myLoanCount' => (clone $myLoans)->count(),
            'myPendingCount' => (clone $myLoans)->where('status', 'Pending')->count(),
            'myCompletedCount' => (clone $myLoans)->where('status', 'Selesai')->count(),
            'recentLoans' => $loans->take(6)->get(),
            'topAlat' => Alat::with('kategori')->orderByDesc('stok_total')->take(5)->get(),
            'handoverCount' => $isTeknisi ? $approvedLoans->filter(fn ($loan) => $loan->detail->contains(fn ($detail) => $detail->penyerahanDetail->sum('jumlah') < $detail->jumlah))->count() : 0,
            'activeReturnCount' => $isTeknisi ? $approvedLoans->filter(fn ($loan) => $loan->detail->contains(fn ($detail) => $detail->penyerahanDetail->sum('jumlah') > $detail->pengembalianDetail->sum('jumlah')))->count() : 0,
        ]);
    }
}
