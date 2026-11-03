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
        $loans = Peminjaman::with(['user', 'detail.alat', 'detail.penyerahanDetail'])
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

            DetailPenyerahan::create([
                'id_detail' => $detail->id_detail,
                'id_teknisi' => $request->user()->id_user,
                'jumlah' => $data['jumlah'],
                'tanggal_penyerahan' => now(),
                'kondisi_alat' => $data['kondisi_alat'],
                'foto_bukti' => $data['foto_bukti'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]);
        });

        return back()->with('success', 'Penyerahan alat berhasil dicatat.');
    }
}
