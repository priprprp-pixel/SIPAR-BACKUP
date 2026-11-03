<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPengembalian;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\ItemUnit;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function verificationIndex(): View
    {
        $pendingReturns = DetailPengembalian::with([
            'detailPeminjaman.peminjaman.user',
            'detailPeminjaman.alat',
            'itemUnit',
            'teknisi',
        ])->where('status_verifikasi', 'Pending')->latest()->paginate(12);

        return view('admin.return-verifications', compact('pendingReturns'));
    }

    public function approveReturn(DetailPengembalian $return): RedirectResponse
    {
        try {
            DB::transaction(function () use ($return): void {
                $pending = DetailPengembalian::whereKey($return->id_detail_pengembalian)
                    ->where('status_verifikasi', 'Pending')
                    ->lockForUpdate()
                    ->firstOrFail();
                $detail = DetailPeminjaman::whereKey($pending->id_detail)->lockForUpdate()->firstOrFail();
                $loan = Peminjaman::whereKey($detail->id_peminjaman)->lockForUpdate()->firstOrFail();
                if ($loan->status !== 'Approved') {
                    throw ValidationException::withMessages(['return' => 'Transaksi ini sudah tidak aktif.']);
                }

                $equipment = Alat::whereKey($detail->id_alat)->lockForUpdate()->firstOrFail();
                if ($pending->item_unit_id) {
                    $unit = ItemUnit::whereKey($pending->item_unit_id)
                        ->where('status', 'Dipinjam')
                        ->whereHas('details', fn ($query) => $query->where('detail_peminjaman.id_detail', $detail->id_detail))
                        ->lockForUpdate()->first();
                    if (! $unit) {
                        throw ValidationException::withMessages(['return' => 'Unit fisik sudah berubah atau tidak sesuai transaksi.']);
                    }
                    $unit->update([
                        'status' => 'Tersedia',
                        'condition' => $pending->kondisi_alat === 'Baik' ? 'Baik' : ($pending->kondisi_alat === 'Maintenance' ? 'Maintenance' : 'Rusak'),
                    ]);
                }

                $this->restoreStock($equipment, $pending->jumlah, $pending->kondisi_alat);
                $pending->update(['status_verifikasi' => 'Approved']);

                $loanDetails = $loan->detail()->with(['pengembalianDetail' => fn ($query) => $query->where('status_verifikasi', 'Approved')])->get();
                $willComplete = $loanDetails->every(fn ($loanDetail): bool => $loanDetail->pengembalianDetail->sum('jumlah') >= $loanDetail->jumlah);
                if ($willComplete) {
                    $approvedReturns = DetailPengembalian::whereIn('id_detail', $loanDetails->pluck('id_detail'))
                        ->where('status_verifikasi', 'Approved')->get();
                    $condition = $approvedReturns->contains('kondisi_alat', 'Hilang')
                        ? 'Hilang' : ($approvedReturns->contains('kondisi_alat', 'Rusak') ? 'Rusak' : 'Baik');
                    Pengembalian::create([
                        'id_peminjaman' => $loan->id_peminjaman,
                        'tanggal_kembali_aktual' => $approvedReturns->max('tanggal_kembali_aktual'),
                        'kondisi_alat' => $condition,
                        'denda' => $approvedReturns->sum('denda'),
                        'catatan' => $approvedReturns->pluck('catatan')->filter()->unique()->implode("\n") ?: null,
                    ]);
                    $loan->update(['status' => 'Selesai']);
                }
            });
        } catch (QueryException $exception) {
            report($exception);
            return back()->withErrors(['return' => 'Verifikasi gagal disimpan. Silakan coba kembali.']);
        }

        return back()->with('success', 'Pengembalian disetujui dan stok telah diperbarui.');
    }

    public function index(): View
    {
        $query = Peminjaman::with(['user', 'detail.alat', 'detail.pengembalianDetail', 'detail.itemUnits'])
            ->where('status', 'Approved');
        if (auth()->user()->role === 'admin') {
            $query->whereDoesntHave('detail.itemUnits');
        }
        $activeLoans = $query->latest('tanggal_pengajuan')->paginate(12);

        return view('admin.returns', compact('activeLoans'));
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();
        if ($term === '') {
            return response()->json(['message' => 'Masukkan kode atau nama alat.'], 422);
        }
        $equipment = Alat::where('kode_alat', $term)
            ->orWhere('nama_alat', 'like', '%'.$term.'%')
            ->first();
        if (! $equipment) {
            return response()->json(['message' => 'Alat tidak ditemukan. Gunakan kode atau nama alat yang benar.'], 404);
        }

        $loansQuery = Peminjaman::with(['user', 'detail.alat', 'detail.pengembalianDetail', 'detail.itemUnits'])
            ->where('status', 'Approved')
            ->whereHas('detail', fn ($query) => $query->where('id_alat', $equipment->id_alat))
            ;
        if (auth()->user()->role === 'admin') {
            $loansQuery->whereDoesntHave('detail.itemUnits');
        }
        $loans = $loansQuery->get()
            ->flatMap(function (Peminjaman $loan) use ($equipment) {
                return $loan->detail
                    ->where('id_alat', $equipment->id_alat)
                    ->map(function ($detail) use ($loan) {
                        $remaining = max(0, $detail->jumlah - $detail->pengembalianDetail->where('status_verifikasi', 'Approved')->sum('jumlah'));
                        if ($remaining === 0) {
                            return null;
                        }

                        return [
                            'id' => $loan->id_peminjaman,
                            'id_detail' => $detail->id_detail,
                            'sisa' => $remaining,
                            'kode' => 'PJM-'.str_pad((string) $loan->id_peminjaman, 5, '0', STR_PAD_LEFT),
                            'peminjam' => $loan->user->nama,
                            'nis_nip' => $loan->user->nis_nip,
                            'jatuh_tempo' => $loan->tanggal_kembali_rencana->format('d M Y'),
                            'units' => $detail->itemUnits->where('status', 'Dipinjam')->map(fn ($unit) => [
                                'id' => $unit->item_unit_id,
                                'asset_id' => $unit->asset_id,
                            ])->values(),
                        ];
                    })
                    ->filter();
            })
            ->values();

        $response = [
            'alat' => $equipment->nama_alat,
            'kode_alat' => $equipment->kode_alat,
            'status' => $equipment->status_alat,
            'stok_tersedia' => $equipment->stok_tersedia,
            'stok_total' => $equipment->stok_total,
            'peminjaman' => $loans,
        ];

        return response()->json($response);
    }

    public function store(Request $request): RedirectResponse
    {
        $photoRules = $request->user()?->role === 'teknisi'
            ? ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']
            : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        $data = $request->validate([
            'id_peminjaman' => ['required', 'integer', 'exists:peminjaman,id_peminjaman'],
            'id_detail' => ['nullable', 'integer', 'exists:detail_peminjaman,id_detail'],
            'item_unit_id' => ['nullable', 'integer', 'exists:item_units,item_unit_id'],
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'kode_alat' => ['required', 'string', 'exists:alat,kode_alat'],
            'tanggal_kembali_aktual' => ['nullable', 'date'],
            'kondisi_alat' => ['required', 'in:Baik,Maintenance,Rusak,Hilang'],
            'foto_bukti' => $photoRules,
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($request->hasFile('foto_bukti')) {
            $data['foto_bukti'] = $request->file('foto_bukti')->store('bukti/pengembalian', 'public');
        }
        $data['id_teknisi'] = $request->user()->role === 'teknisi' ? $request->user()->id_user : null;
        $equipment = Alat::where('kode_alat', $data['kode_alat'])->firstOrFail();

        $fine = 0;
        $completed = false;
        $requiresHandover = $request->user()->role === 'teknisi';
        try {
            DB::transaction(function () use ($data, $requiresHandover, &$fine, &$completed) {
                $loan = Peminjaman::whereKey($data['id_peminjaman'])->lockForUpdate()->firstOrFail();
                if ($loan->status !== 'Approved' || $loan->pengembalian()->exists()) {
                    throw ValidationException::withMessages(['id_peminjaman' => 'Transaksi ini sudah selesai atau tidak sedang dipinjam.']);
                }
                $equipment = Alat::where('kode_alat', $data['kode_alat'])->lockForUpdate()->firstOrFail();
                $detailQuery = $loan->detail()->where('id_alat', $equipment->id_alat);
                if (! empty($data['id_detail'])) {
                    $detailQuery->where('id_detail', $data['id_detail']);
                }
                $detail = $detailQuery->lockForUpdate()->first();
                if (! $detail) {
                    throw ValidationException::withMessages(['kode_alat' => 'Kode alat tidak sesuai dengan item pada transaksi ini.']);
                }
                $assignedUnits = $detail->itemUnits()->where('status', 'Dipinjam')->lockForUpdate()->get();
                $hasAssignedUnits = $assignedUnits->isNotEmpty();
                if ($hasAssignedUnits && empty($data['item_unit_id'])) {
                    throw ValidationException::withMessages(['item_unit_id' => 'Pilih unit fisik yang dikembalikan.']);
                }
                $itemUnit = null;
                if (! empty($data['item_unit_id'])) {
                    $itemUnit = ItemUnit::whereKey($data['item_unit_id'])
                        ->where('status', 'Dipinjam')
                        ->whereHas('details', fn ($query) => $query->where('detail_peminjaman.id_detail', $detail->id_detail))
                        ->lockForUpdate()
                        ->first();
                    if (! $itemUnit) {
                        throw ValidationException::withMessages(['item_unit_id' => 'Unit fisik tidak sesuai transaksi atau sudah dikembalikan.']);
                    }
                    if (($data['jumlah'] ?? 1) !== 1) {
                        throw ValidationException::withMessages(['jumlah' => 'Pengembalian unit fisik harus satu unit per proses.']);
                    }
                    if (! $detail->penyerahanDetail()->where('item_unit_id', $itemUnit->item_unit_id)->exists()) {
                        throw ValidationException::withMessages(['item_unit_id' => 'Unit ini belum tercatat dalam penyerahan.']);
                    }
                }

                if ($requiresHandover && $detail->pengembalianDetail()->where('status_verifikasi', 'Pending')->exists()) {
                    throw ValidationException::withMessages(['id_peminjaman' => 'Pengembalian transaksi ini sedang menunggu verifikasi Admin.']);
                }
                $returnedQuantity = $detail->pengembalianDetail()->where('status_verifikasi', 'Approved')->sum('jumlah');
                $remainingQuantity = max(0, $detail->jumlah - $returnedQuantity);
                $quantity = (int) ($data['jumlah'] ?? $remainingQuantity);
                if ($hasAssignedUnits && $quantity !== 1) {
                    throw ValidationException::withMessages(['jumlah' => 'Pengembalian unit fisik harus satu unit per proses.']);
                }
                if ($remainingQuantity === 0 || $quantity > $remainingQuantity) {
                    throw ValidationException::withMessages(['jumlah' => 'Jumlah pengembalian melebihi sisa alat pada transaksi.']);
                }
                $handedOver = $detail->penyerahanDetail()->sum('jumlah');
                $returnedBefore = $detail->pengembalianDetail()->sum('jumlah');
                if (($requiresHandover || $hasAssignedUnits)
                    && $handedOver - $returnedBefore < $quantity) {
                    throw ValidationException::withMessages(['jumlah' => 'Alat belum tercatat diserahkan atau jumlah pengembalian melebihi penyerahan.']);
                }

                $returnedAt = now();
                $lateDays = max(0, $loan->tanggal_kembali_rencana->copy()->startOfDay()->diffInDays($returnedAt->copy()->startOfDay(), false));
                $loanDetails = $loan->detail()->with('pengembalianDetail')->get();
                $willComplete = $loanDetails->every(function ($loanDetail) use ($detail, $quantity): bool {
                    $returned = $loanDetail->pengembalianDetail->sum('jumlah');
                    if ($loanDetail->id_detail === $detail->id_detail) {
                        $returned += $quantity;
                    }

                    return $returned >= $loanDetail->jumlah;
                });
                $priorFine = DetailPengembalian::whereIn('id_detail', $loanDetails->pluck('id_detail'))->sum('denda');
                $totalFine = $lateDays * 5000;
                $fine = $willComplete
                    ? max(0, round($totalFine - $priorFine, 2))
                    : round(($totalFine * $quantity) / max(1, (int) $loanDetails->sum('jumlah')), 2);

                DetailPengembalian::create([
                    'id_detail' => $detail->id_detail,
                    'item_unit_id' => $itemUnit?->item_unit_id,
                    'jumlah' => $quantity,
                    'tanggal_kembali_aktual' => $returnedAt,
                    'kondisi_alat' => $data['kondisi_alat'],
                    'foto_bukti' => $data['foto_bukti'] ?? null,
                    'denda' => $fine,
                    'catatan' => $data['catatan'] ?? null,
                    'status_verifikasi' => $requiresHandover ? 'Pending' : 'Approved',
                    'id_teknisi' => $requiresHandover ? $data['id_teknisi'] ?? null : null,
                ]);
                if ($requiresHandover) {
                    return;
                }
                if ($itemUnit) {
                    $itemUnit->update([
                        'status' => 'Tersedia',
                        'condition' => $data['kondisi_alat'] === 'Baik' ? 'Baik' : ($data['kondisi_alat'] === 'Maintenance' ? 'Maintenance' : 'Rusak'),
                    ]);
                }

                $equipment->refresh();
                if ($data['kondisi_alat'] === 'Baik') {
                    $equipment->stok_tersedia += $quantity;
                } elseif ($data['kondisi_alat'] === 'Maintenance' || $data['kondisi_alat'] === 'Rusak') {
                    $equipment->stok_maintenance += $quantity;
                    if ($data['kondisi_alat'] === 'Rusak') {
                        $equipment->kondisi = 'Rusak';
                    }
                } elseif ($data['kondisi_alat'] === 'Hilang') {
                    $equipment->stok_total = max(0, $equipment->stok_total - $quantity);
                } else {
                    $equipment->stok_total = max(0, $equipment->stok_total - $quantity);
                }
                $equipment->syncStatusFromStock();
                $equipment->save();

                $completed = $willComplete;
                if ($completed) {
                    $allReturns = DetailPengembalian::whereIn('id_detail', $loanDetails->pluck('id_detail'))->get();
                    $condition = $allReturns->contains('kondisi_alat', 'Hilang')
                        ? 'Hilang'
                        : ($allReturns->contains('kondisi_alat', 'Rusak') ? 'Rusak' : 'Baik');
                    $notes = $allReturns->pluck('catatan')->filter()->unique()->implode("\n");

                    Pengembalian::create([
                        'id_peminjaman' => $loan->id_peminjaman,
                        'tanggal_kembali_aktual' => $allReturns->max('tanggal_kembali_aktual'),
                        'kondisi_alat' => $condition,
                        'denda' => $allReturns->sum('denda'),
                        'catatan' => $notes !== '' ? $notes : null,
                    ]);
                    $loan->update(['status' => 'Selesai']);
                }
            });
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['id_peminjaman' => 'Pengembalian gagal disimpan. Silakan coba kembali.']);
        }

        $message = $completed
            ? 'Semua alat pada transaksi telah kembali. Denda: Rp '.number_format($fine, 0, ',', '.')
            : 'Pengembalian item dicatat; transaksi tetap aktif sampai seluruh alat kembali. Denda item ini: Rp '.number_format($fine, 0, ',', '.');

        $returnRoute = $request->user()->role === 'teknisi' ? 'technician.returns' : 'admin.returns';

        return redirect()->route($returnRoute)->with('success', $message);
    }

    private function restoreStock(Alat $equipment, int $quantity, string $condition): void
    {
        if ($condition === 'Baik') {
            $equipment->stok_tersedia += $quantity;
        } elseif ($condition === 'Maintenance' || $condition === 'Rusak') {
            $equipment->stok_maintenance += $quantity;
            if ($condition === 'Rusak') {
                $equipment->kondisi = 'Rusak';
            }
        } else {
            $equipment->stok_total = max(0, $equipment->stok_total - $quantity);
        }

        $equipment->syncStatusFromStock();
        $equipment->save();
    }
}
