<?php

namespace App\Http\Controllers;

use App\Models\DetailPenyerahan;
use App\Models\Peminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TechnicianController extends Controller
{
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
            'foto_bukti' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
