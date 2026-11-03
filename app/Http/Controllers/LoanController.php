<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\ItemUnit;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function catalog(Request $request): View
    {
        $equipment = Alat::with('kategori')->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
            $term = '%'.$request->string('q')->trim().'%';
            $query->where(fn ($builder) => $builder->where('nama_alat', 'like', $term)->orWhere('kode_alat', 'like', $term));
        })->orderBy('nama_alat')->paginate(12)->withQueryString();

        return view('catalog', compact('equipment'));
    }

    public function create(Request $request): RedirectResponse
    {
        if ($request->filled('id_alat') && ! $request->has('items')) {
            $request->merge(['items' => [[
                'id_alat' => $request->input('id_alat'),
                'jumlah' => $request->input('jumlah'),
            ]]]);
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.id_alat' => ['required', 'integer', 'distinct', 'exists:alat,id_alat'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:100'],
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:today'],
            'tanggal_kembali_rencana' => ['required', 'date', 'after_or_equal:tanggal_pinjam', 'before_or_equal:'.now()->addDays(5)->toDateString()],
            'keperluan' => ['required', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($request, $data) {
                $requestedItems = collect($data['items'])->keyBy(fn (array $item) => (int) $item['id_alat']);
                $equipmentList = Alat::whereIn('id_alat', $requestedItems->keys())->orderBy('id_alat')->lockForUpdate()->get();

                foreach ($equipmentList as $equipment) {
                    $quantity = (int) $requestedItems[$equipment->id_alat]['jumlah'];
                    if ($equipment->status_alat !== 'Tersedia' || $equipment->stok_tersedia < $quantity) {
                        $message = $equipment->status_alat !== 'Tersedia'
                            ? "{$equipment->nama_alat} sedang {$equipment->status_alat} dan tidak dapat dipinjam."
                            : "Jumlah {$equipment->nama_alat} melebihi stok yang tersedia ({$equipment->stok_tersedia}); pengajuan tidak disimpan.";
                        throw ValidationException::withMessages([
                            'items' => $message,
                        ]);
                    }
                }

                $loan = Peminjaman::create([
                    'id_user' => $request->user()->id_user,
                    'tanggal_pinjam' => $data['tanggal_pinjam'],
                    'tanggal_kembali_rencana' => $data['tanggal_kembali_rencana'],
                    'keperluan' => $data['keperluan'],
                    'status' => 'Pending',
                ]);
                foreach ($equipmentList as $equipment) {
                    $loan->detail()->create([
                        'id_alat' => $equipment->id_alat,
                        'jumlah' => (int) $requestedItems[$equipment->id_alat]['jumlah'],
                    ]);
                }
            });
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['jumlah' => 'Pengajuan belum dapat disimpan. Silakan coba kembali.'])->withInput();
        }

        return redirect()->route('loans.mine')->with('success', 'Pengajuan tersimpan dan menunggu verifikasi.');
    }

    public function mine(Request $request): View
    {
        $loans = Peminjaman::with(['detail.alat', 'detail.pengembalianDetail', 'pengembalian'])
            ->where('id_user', $request->user()->id_user)
            ->latest('tanggal_pengajuan')->paginate(10);

        return view('loans.mine', compact('loans'));
    }

    public function approvals(): View
    {
        $loans = Peminjaman::with(['user', 'detail.alat'])
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereDoesntHave('detail.alat.itemUnits')
            ->latest('tanggal_pengajuan')->paginate(12);

        return view('admin.approvals', compact('loans'));
    }

    public function cancel(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $cancelled = Peminjaman::whereKey($peminjaman->id_peminjaman)
            ->where('id_user', $request->user()->id_user)
            ->where('status', 'Pending')
            ->update(['status' => 'Cancelled']);

        if (! $cancelled) {
            return back()->withErrors(['status' => 'Hanya pengajuan yang masih Pending yang dapat dibatalkan.']);
        }

        return back()->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    }

    public function approve(Peminjaman $peminjaman): RedirectResponse
    {
        try {
            DB::transaction(function () use ($peminjaman) {
                $loan = Peminjaman::whereKey($peminjaman->id_peminjaman)->lockForUpdate()->firstOrFail();
                if ($loan->status !== 'Pending') {
                    throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
                }
                if (ItemUnit::whereIn('alat_id', $loan->detail->pluck('id_alat'))->exists()) {
                    throw ValidationException::withMessages(['status' => 'Pengajuan dengan unit fisik harus disetujui melalui Teknisi.']);
                }

                foreach ($loan->detail as $detail) {
                    $equipment = Alat::whereKey($detail->id_alat)->lockForUpdate()->firstOrFail();
                    if ($equipment->status_alat !== 'Tersedia' || $equipment->stok_tersedia < $detail->jumlah) {
                        throw ValidationException::withMessages(['stok' => "Stok {$equipment->nama_alat} tidak lagi mencukupi."]);
                    }
                    $equipment->stok_tersedia -= $detail->jumlah;
                    $equipment->syncStatusFromStock();
                    $equipment->save();
                }

                $loan->update(['status' => 'Approved']);
            });
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['status' => 'Persetujuan gagal disimpan. Silakan coba kembali.']);
        }

        return back()->with('success', 'Pengajuan disetujui dan stok diperbarui.');
    }

    public function reject(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate(['catatan_admin' => ['required', 'string', 'max:1000']]);
        $updated = Peminjaman::whereKey($peminjaman->id_peminjaman)
            ->where('status', 'Pending')
            ->update(['status' => 'Rejected', 'catatan_admin' => $data['catatan_admin']]);

        if (! $updated) {
            return back()->withErrors(['status' => 'Hanya pengajuan Pending yang dapat ditolak.']);
        }

        return back()->with('success', 'Pengajuan ditolak dan catatan tersimpan.');
    }

}
