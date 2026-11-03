<?php

namespace App\Http\Controllers;

use App\Models\DetailPenyerahan;
use App\Models\ItemUnit;
use App\Models\Peminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TechnicianController extends Controller
{
    public function approvals(): View
    {
        $loans = Peminjaman::with(['user', 'detail.alat'])
            ->where('status', 'Pending')
            ->whereHas('detail.alat.itemUnits')
            ->whereDoesntHave('detail.alat', fn ($query) => $query->whereDoesntHave('itemUnits'))
            ->latest('tanggal_pengajuan')
            ->paginate(12);

        return view('technician.approvals', compact('loans'));
    }

    public function approve(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate(['units' => ['required', 'array']]);

        try {
            DB::transaction(function () use ($request, $peminjaman, $data): void {
                $loan = Peminjaman::whereKey($peminjaman->id_peminjaman)
                    ->with('detail')
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($loan->status !== 'Pending') {
                    throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
                }

                foreach ($loan->detail as $detail) {
                    $selected = collect($data['units'][(string) $detail->id_detail] ?? $data['units'][$detail->id_detail] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();
                    if ($selected->count() !== (int) $detail->jumlah) {
                        throw ValidationException::withMessages([
                            'units' => "Pilih tepat {$detail->jumlah} unit untuk {$detail->alat()->value('nama_alat')}.",
                        ]);
                    }

                    $units = ItemUnit::whereIn('item_unit_id', $selected)
                        ->where('alat_id', $detail->id_alat)
                        ->where('condition', 'Baik')
                        ->where('status', 'Tersedia')
                        ->lockForUpdate()
                        ->get();
                    if ($units->count() !== $selected->count()) {
                        throw ValidationException::withMessages(['units' => 'Ada unit yang sudah dipakai atau tidak memenuhi kondisi Baik. Muat ulang halaman dan pilih ulang.']);
                    }

                    $detail->itemUnits()->attach($units->pluck('item_unit_id')->all(), ['assigned_by' => $request->user()->id_user]);
                    ItemUnit::whereIn('item_unit_id', $units->pluck('item_unit_id'))->update(['status' => 'Dipinjam']);

                    $equipment = $detail->alat()->lockForUpdate()->firstOrFail();
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

        return back()->with('success', 'Pengajuan disetujui dan unit fisik berhasil dikunci.');
    }

    public function handovers(): View
    {
        $loans = Peminjaman::with(['user', 'detail.alat.itemUnits', 'detail.itemUnits', 'detail.penyerahanDetail'])
            ->where('status', 'Approved')
            ->latest('tanggal_pengajuan')
            ->paginate(12);

        return view('technician.handovers', compact('loans'));
    }

    public function storeHandover(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate([
            'id_detail' => ['required', 'integer', 'exists:detail_peminjaman,id_detail'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:10000'],
            'item_unit_ids' => ['nullable', 'array'],
            'item_unit_ids.*' => ['integer', 'distinct', 'exists:item_units,item_unit_id'],
            'kondisi_alat' => ['required', 'in:Baik,Rusak'],
            'foto_bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($request->hasFile('foto_bukti')) {
            $data['foto_bukti'] = $request->file('foto_bukti')->store('bukti/penyerahan', 'public');
        }

        DB::transaction(function () use ($request, $peminjaman, $data): void {
            $loan = Peminjaman::whereKey($peminjaman->id_peminjaman)->lockForUpdate()->firstOrFail();
            if ($loan->status !== 'Approved') {
                throw ValidationException::withMessages(['id_detail' => 'Hanya transaksi yang sudah disetujui yang dapat diserahkan.']);
            }
            $detail = $loan->detail()->whereKey($data['id_detail'])->lockForUpdate()->firstOrFail();
            $handedOver = $detail->penyerahanDetail()->sum('jumlah');
            if ($handedOver + $data['jumlah'] > $detail->jumlah) {
                throw ValidationException::withMessages(['jumlah' => 'Jumlah penyerahan melebihi jumlah pengajuan.']);
            }

            $assignedUnits = $detail->itemUnits()->where('status', 'Dipinjam')->get();
            $selectedUnitIds = collect($data['item_unit_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
            if ($assignedUnits->isNotEmpty()) {
                if ($data['kondisi_alat'] !== 'Baik') {
                    throw ValidationException::withMessages(['kondisi_alat' => 'Unit fisik hanya dapat diserahkan jika kondisinya Baik.']);
                }
                $alreadyHandedUnitIds = $detail->penyerahanDetail()->whereNotNull('item_unit_id')->pluck('item_unit_id');
                $availableUnitIds = $assignedUnits->pluck('item_unit_id')->diff($alreadyHandedUnitIds);
                if ($selectedUnitIds->count() !== $data['jumlah'] || $selectedUnitIds->diff($availableUnitIds)->isNotEmpty()) {
                    throw ValidationException::withMessages(['item_unit_ids' => 'Pilih tepat unit fisik yang belum diserahkan.']);
                }
                foreach ($selectedUnitIds as $itemUnitId) {
                    DetailPenyerahan::create([
                        'id_detail' => $detail->id_detail,
                        'item_unit_id' => $itemUnitId,
                        'id_teknisi' => $request->user()->id_user,
                        'jumlah' => 1,
                        'tanggal_penyerahan' => now(),
                        'kondisi_alat' => $data['kondisi_alat'],
                        'foto_bukti' => $data['foto_bukti'] ?? null,
                        'catatan' => $data['catatan'] ?? null,
                    ]);
                }
            } else {
                DetailPenyerahan::create([
                    'id_detail' => $detail->id_detail,
                    'id_teknisi' => $request->user()->id_user,
                    'jumlah' => $data['jumlah'],
                    'tanggal_penyerahan' => now(),
                    'kondisi_alat' => $data['kondisi_alat'],
                    'foto_bukti' => $data['foto_bukti'] ?? null,
                    'catatan' => $data['catatan'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Penyerahan alat berhasil dicatat.');
    }
}
