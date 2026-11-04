<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\KategoriAlat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%'.$term.'%';
        $results = Alat::with('kategori')
            ->where(fn (Builder $query) => $query
                ->where('nama_alat', 'like', $like)
                ->orWhere('kode_alat', 'like', $like)
                ->orWhereHas('kategori', fn (Builder $category) => $category->where('nama_kategori', 'like', $like)))
            ->orderBy('nama_alat')
            ->take(6)
            ->get()
            ->map(fn (Alat $equipment): array => [
                'type' => 'Alat',
                'title' => $equipment->nama_alat,
                'subtitle' => $equipment->kode_alat.' · '.($equipment->kategori?->nama_kategori ?? 'Tanpa kategori'),
                'url' => route('search', ['q' => $equipment->kode_alat]),
            ]);

        $categories = KategoriAlat::where('nama_kategori', 'like', $like)
            ->orderBy('nama_kategori')
            ->take(4)
            ->get()
            ->map(fn (KategoriAlat $category): array => [
                'type' => 'Kategori',
                'title' => $category->nama_kategori,
                'subtitle' => 'Kategori alat',
                'url' => route('search', ['q' => $category->nama_kategori]),
            ]);

        $loans = Peminjaman::with('user')
            ->when($request->user()->role !== 'admin', fn (Builder $query) => $query->where('id_user', $request->user()->id_user))
            ->where(fn (Builder $query) => $query
                ->where('keperluan', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->when(preg_match('/^(?:PJM-)?\d+$/i', $term), fn (Builder $query) => $query->orWhere('id_peminjaman', (int) preg_replace('/^PJM-/i', '', $term))))
            ->latest('tanggal_pengajuan')
            ->take(4)
            ->get()
            ->map(fn (Peminjaman $loan): array => [
                'type' => 'Transaksi',
                'title' => 'PJM-'.str_pad((string) $loan->id_peminjaman, 5, '0', STR_PAD_LEFT),
                'subtitle' => $loan->status_label.' · '.$loan->user->nama,
                'url' => route('search', ['q' => 'PJM-'.$loan->id_peminjaman]),
            ]);

        if ($request->user()->role === 'admin') {
            $students = User::where('role', 'siswa')
                ->where(fn (Builder $query) => $query
                    ->where('nama', 'like', $like)
                    ->orWhere('nis_nip', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->orderBy('nama')
                ->take(4)
                ->get()
                ->map(fn (User $student): array => [
                    'type' => 'Siswa',
                    'title' => $student->nama,
                    'subtitle' => $student->nis_nip.' · '.$student->email,
                    'url' => route('search', ['q' => $student->nis_nip]),
                ]);
            $results = $results->concat($categories)->concat($loans)->concat($students);
        } else {
            $results = $results->concat($categories)->concat($loans);
        }

        return response()->json(['results' => $results->take(12)->values()]);
    }

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