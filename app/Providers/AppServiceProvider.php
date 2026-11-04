<?php

namespace App\Providers;

use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.bootstrap-5');
        View::composer('layouts.app', function ($view): void {
            $technicianHandoverCount = 0;
            $technicianReturnCount = 0;
            if (auth()->check() && auth()->user()->role === 'teknisi') {
                $technicianHandoverCount = DetailPeminjaman::whereHas('peminjaman', fn ($query) => $query->where('status', 'Approved'))
                    ->whereRaw('detail_peminjaman.jumlah > (
                        SELECT COALESCE(SUM(detail_penyerahan.jumlah), 0)
                        FROM detail_penyerahan
                        WHERE detail_penyerahan.id_detail = detail_peminjaman.id_detail
                    )')
                    ->count();

                $technicianReturnCount = Peminjaman::where('status', 'Approved')
                    ->whereHas('detail', fn ($query) => $query->whereRaw('(
                        SELECT COALESCE(SUM(detail_penyerahan.jumlah), 0)
                        FROM detail_penyerahan
                        WHERE detail_penyerahan.id_detail = detail_peminjaman.id_detail
                    ) > (
                        SELECT COALESCE(SUM(detail_pengembalian.jumlah), 0)
                        FROM detail_pengembalian
                        WHERE detail_pengembalian.id_detail = detail_peminjaman.id_detail
                    )'))
                    ->count();
            }

            $view->with(compact('technicianHandoverCount', 'technicianReturnCount'));
        });
    }
}
