<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $touchedLoanIds = [];
            $pendingReturns = DB::table('detail_pengembalian')
                ->where('status_verifikasi', 'Pending')
                ->orderBy('id_detail_pengembalian')
                ->get();

            foreach ($pendingReturns as $return) {
                $detail = DB::table('detail_peminjaman')->where('id_detail', $return->id_detail)->first();
                $equipment = DB::table('alat')->where('id_alat', $detail->id_alat)->first();
                $available = (int) $equipment->stok_tersedia;
                $maintenance = (int) $equipment->stok_maintenance;
                $damaged = (int) $equipment->stok_rusak;
                $total = (int) $equipment->stok_total;

                if ($return->kondisi_alat === 'Baik') {
                    $available += (int) $return->jumlah;
                } elseif ($return->kondisi_alat === 'Maintenance' || $return->kondisi_alat === 'Rusak') {
                    $maintenance += (int) $return->jumlah;
                } else {
                    $total = max(0, $total - (int) $return->jumlah);
                }

                $borrowed = max(0, $total - $available - $maintenance - $damaged);
                $status = match (true) {
                    $available > 0 => 'Tersedia',
                    $borrowed > 0 => 'Dipinjam',
                    $maintenance > 0 => 'Maintenance',
                    $damaged > 0 => 'Rusak',
                    default => 'Rusak',
                };
                DB::table('alat')->where('id_alat', $equipment->id_alat)->update([
                    'stok_total' => $total,
                    'stok_tersedia' => $available,
                    'stok_maintenance' => $maintenance,
                    'status_alat' => $status,
                    'kondisi' => $return->kondisi_alat === 'Rusak' ? 'Rusak' : $equipment->kondisi,
                    'updated_at' => $now,
                ]);

                if ($return->item_unit_id) {
                    DB::table('item_units')->where('item_unit_id', $return->item_unit_id)
                        ->where('status', 'Dipinjam')
                        ->update([
                            'status' => 'Tersedia',
                            'condition' => $return->kondisi_alat === 'Baik'
                                ? 'Baik'
                                : ($return->kondisi_alat === 'Maintenance' ? 'Maintenance' : 'Rusak'),
                            'updated_at' => $now,
                        ]);
                }

                DB::table('detail_pengembalian')
                    ->where('id_detail_pengembalian', $return->id_detail_pengembalian)
                    ->update(['status_verifikasi' => 'Approved', 'updated_at' => $now]);
                $touchedLoanIds[] = $detail->id_peminjaman;
            }

            foreach (array_unique($touchedLoanIds) as $loanId) {
                $loan = DB::table('peminjaman')->where('id_peminjaman', $loanId)->first();
                if (! $loan || $loan->status !== 'Approved') {
                    continue;
                }

                $details = DB::table('detail_peminjaman')->where('id_peminjaman', $loanId)->get();
                $complete = $details->every(function ($detail): bool {
                    $returned = DB::table('detail_pengembalian')
                        ->where('id_detail', $detail->id_detail)
                        ->sum('jumlah');

                    return $returned >= $detail->jumlah;
                });
                if (! $complete) {
                    continue;
                }

                $returns = DB::table('detail_pengembalian')
                    ->whereIn('id_detail', $details->pluck('id_detail'))
                    ->orderBy('tanggal_kembali_aktual')
                    ->get();
                $condition = $returns->contains('kondisi_alat', 'Hilang')
                    ? 'Hilang'
                    : ($returns->contains('kondisi_alat', 'Rusak') ? 'Rusak' : 'Baik');
                if (! DB::table('pengembalian')->where('id_peminjaman', $loanId)->exists()) {
                    $notes = $returns->pluck('catatan')->filter()->unique()->implode("\n");
                    DB::table('pengembalian')->insert([
                        'id_peminjaman' => $loanId,
                        'tanggal_kembali_aktual' => $returns->max('tanggal_kembali_aktual'),
                        'kondisi_alat' => $condition,
                        'denda' => $returns->sum('denda'),
                        'catatan' => $notes !== '' ? $notes : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                DB::table('peminjaman')->where('id_peminjaman', $loanId)->update([
                    'status' => 'Selesai',
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Historical returns remain completed; their stock effects must not be reversed.
    }
};
