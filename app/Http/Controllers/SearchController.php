<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $term = trim($data['q'] ?? '');
        $isAdmin = $request->user()->role === 'admin';
        $equipment = collect();
        $students = collect();
        $loans = collect();

        if ($term !== '') {
            $like = '%'.$term.'%';
            $equipment = Alat::with('kategori')
                ->where(fn (Builder $query) => $query
                    ->where('nama_alat', 'like', $like)
                    ->orWhere('kode_alat', 'like', $like)
                    ->orWhereHas('kategori', fn (Builder $category) => $category->where('nama_kategori', 'like', $like)))
                ->orderBy('nama_alat')
                ->take(10)
                ->get();

            if ($isAdmin) {
                $students = User::where('role', 'siswa')
                    ->where(fn (Builder $query) => $query
                        ->where('nama', 'like', $like)
                        ->orWhere('nis_nip', 'like', $like)
                        ->orWhere('email', 'like', $like))
                    ->orderBy('nama')
                    ->take(10)
                    ->get();
            }

            $loanQuery = Peminjaman::with(['user', 'detail.alat']);
            if (! $isAdmin) {
                $loanQuery->where('id_user', $request->user()->id_user);
            }

            $loanId = preg_match('/^(?:PJM-)?\d+$/i', $term)
                ? (int) preg_replace('/^PJM-/i', '', $term)
                : null;

            $loans = $loanQuery
                ->where(fn (Builder $query) => $query
                    ->where('keperluan', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->when($loanId !== null, fn (Builder $query) => $query->orWhere('id_peminjaman', $loanId))
                    ->orWhereHas('detail.alat', fn (Builder $equipmentQuery) => $equipmentQuery
                        ->where('nama_alat', 'like', $like)
                        ->orWhere('kode_alat', 'like', $like))
                    ->when($isAdmin, fn (Builder $query) => $query->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('nama', 'like', $like)
                        ->orWhere('nis_nip', 'like', $like))))
                ->latest('tanggal_pengajuan')
                ->take(10)
                ->get();
        }

        return view('search.index', compact('term', 'isAdmin', 'equipment', 'students', 'loans'));
    }
}