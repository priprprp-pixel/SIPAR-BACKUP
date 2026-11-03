<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPengembalian;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\ItemUnit;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReturnController extends Controller
{
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

    public function lookup(string $token): JsonResponse
    {
        $equipment = Alat::where('qr_code', $token)->orWhere('kode_alat', $token)->first();
        if (! $equipment) {
            return response()->json(['message' => 'Alat tidak ditemukan. Periksa QR atau kode alat.'], 404);
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
                        $remaining = max(0, $detail->jumlah - $detail->pengembalianDetail->sum('jumlah'));
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
            'qr_code' => $equipment->qr_code,
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
            'qr_code' => ['nullable', 'required_without:kode_alat', 'string', 'exists:alat,qr_code'],
            'kode_alat' => ['nullable', 'required_without:qr_code', 'string', 'exists:alat,kode_alat'],
            'tanggal_kembali_aktual' => ['nullable', 'date', 'before_or_equal:now'],
            'kondisi_alat' => ['required', 'in:Baik,Maintenance,Rusak,Hilang'],
            'foto_bukti' => $photoRules,
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($request->hasFile('foto_bukti')) {
            $data['foto_bukti'] = $request->file('foto_bukti')->store('bukti/pengembalian', 'public');
        }
        $equipment = isset($data['qr_code'])
            ? Alat::where('qr_code', $data['qr_code'])->firstOrFail()
            : Alat::where('kode_alat', $data['kode_alat'])->firstOrFail();
        $data['qr_code'] = $equipment->qr_code;

        $fine = 0;
        $completed = false;
        $requiresHandover = $request->user()->role === 'teknisi';
        try {
            DB::transaction(function () use ($data, $requiresHandover, &$fine, &$completed) {
                $loan = Peminjaman::whereKey($data['id_peminjaman'])->lockForUpdate()->firstOrFail();
                if ($loan->status !== 'Approved' || $loan->pengembalian()->exists()) {
                    throw ValidationException::withMessages(['id_peminjaman' => 'Transaksi ini sudah selesai atau tidak sedang dipinjam.']);
                }
                $scannedEquipment = Alat::where('qr_code', $data['qr_code'])->lockForUpdate()->firstOrFail();
                $detailQuery = $loan->detail()->where('id_alat', $scannedEquipment->id_alat);
                if (! empty($data['id_detail'])) {
                    $detailQuery->where('id_detail', $data['id_detail']);
                }
                $detail = $detailQuery->lockForUpdate()->first();
                if (! $detail) {
                    throw ValidationException::withMessages(['qr_code' => 'QR alat tidak sesuai dengan item pada transaksi ini.']);
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

                $returnedQuantity = $detail->pengembalianDetail()->sum('jumlah');
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

                $returnedAt = isset($data['tanggal_kembali_aktual'])
                    ? Carbon::parse($data['tanggal_kembali_aktual'])
                    : now();
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
                    'jumlah' => $quantity,
                    'tanggal_kembali_aktual' => $returnedAt,
                    'kondisi_alat' => $data['kondisi_alat'],
                    'foto_bukti' => $data['foto_bukti'] ?? null,
                    'denda' => $fine,
                    'catatan' => $data['catatan'] ?? null,
                ]);
                if ($itemUnit) {
                    $itemUnit->update([
                        'status' => 'Tersedia',
                        'condition' => $data['kondisi_alat'] === 'Baik' ? 'Baik' : ($data['kondisi_alat'] === 'Maintenance' ? 'Maintenance' : 'Rusak'),
                    ]);
                }

                $scannedEquipment->refresh();
                if ($data['kondisi_alat'] === 'Baik') {
                    $scannedEquipment->stok_tersedia += $quantity;
                } elseif ($data['kondisi_alat'] === 'Maintenance' || $data['kondisi_alat'] === 'Rusak') {
                    $scannedEquipment->stok_maintenance += $quantity;
                    if ($data['kondisi_alat'] === 'Rusak') {
                        $scannedEquipment->kondisi = 'Rusak';
                    }
                } elseif ($data['kondisi_alat'] === 'Hilang') {
                    $scannedEquipment->stok_total = max(0, $scannedEquipment->stok_total - $quantity);
                } else {
                    $scannedEquipment->stok_total = max(0, $scannedEquipment->stok_total - $quantity);
                }
                $scannedEquipment->syncStatusFromStock();
                $scannedEquipment->save();

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
}
