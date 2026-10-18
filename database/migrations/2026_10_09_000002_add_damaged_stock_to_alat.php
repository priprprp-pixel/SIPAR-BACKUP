<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alat') && ! Schema::hasColumn('alat', 'stok_rusak')) {
            Schema::table('alat', function (Blueprint $table): void {
                $table->unsignedInteger('stok_rusak')->default(0)->after('stok_maintenance');
            });

            $borrowed = DB::table('detail_peminjaman')
                ->join('peminjaman', 'peminjaman.id_peminjaman', '=', 'detail_peminjaman.id_peminjaman')
                ->where('peminjaman.status', 'Approved')
                ->groupBy('detail_peminjaman.id_alat')
                ->select('detail_peminjaman.id_alat', DB::raw('SUM(detail_peminjaman.jumlah) as jumlah_dipinjam'))
                ->pluck('jumlah_dipinjam', 'id_alat');

            DB::table('alat')
                ->where('status_alat', 'Rusak')
                ->orderBy('id_alat')
                ->get(['id_alat', 'stok_total', 'stok_tersedia', 'stok_maintenance'])
                ->each(function ($equipment) use ($borrowed): void {
                    $inUse = (int) ($borrowed[$equipment->id_alat] ?? 0);
                    $damaged = max(0, $equipment->stok_total - $equipment->stok_tersedia - $equipment->stok_maintenance - $inUse);
                    DB::table('alat')->where('id_alat', $equipment->id_alat)->update(['stok_rusak' => $damaged]);
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('alat') && Schema::hasColumn('alat', 'stok_rusak')) {
            Schema::table('alat', function (Blueprint $table): void {
                $table->dropColumn('stok_rusak');
            });
        }
    }
};
