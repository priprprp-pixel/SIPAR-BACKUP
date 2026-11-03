<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TechnicianController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::get('/daftar', [AuthController::class, 'register'])->name('register');
    Route::post('/daftar', [AuthController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/cari', [SearchController::class, 'index'])->name('search');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profil', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profil', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profil/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('role:siswa')->group(function () {
        Route::get('/katalog', [LoanController::class, 'catalog'])->name('catalog');
        Route::post('/pengajuan', [LoanController::class, 'create'])->name('loans.create');
        Route::post('/pengajuan/{peminjaman}/batal', [LoanController::class, 'cancel'])->name('loans.cancel');
        Route::get('/riwayat', [LoanController::class, 'mine'])->name('loans.mine');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/alat', [MasterDataController::class, 'equipment'])->name('equipment');
        Route::get('/unit-alat', [MasterDataController::class, 'itemUnits'])->name('item-units');
        Route::post('/unit-alat', [MasterDataController::class, 'storeItemUnit'])->name('item-units.store');
        Route::put('/unit-alat/{itemUnit}', [MasterDataController::class, 'updateItemUnit'])->name('item-units.update');
        Route::delete('/unit-alat/{itemUnit}', [MasterDataController::class, 'deleteItemUnit'])->name('item-units.delete');
        Route::post('/alat', [MasterDataController::class, 'storeEquipment'])->name('equipment.store');
        Route::put('/alat/{alat}', [MasterDataController::class, 'updateEquipment'])->name('equipment.update');
        Route::delete('/alat/{alat}', [MasterDataController::class, 'deleteEquipment'])->name('equipment.delete');
        Route::get('/kategori', [MasterDataController::class, 'categories'])->name('categories');
        Route::post('/kategori', [MasterDataController::class, 'storeCategory'])->name('categories.store');
        Route::put('/kategori/{kategori}', [MasterDataController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/kategori/{kategori}', [MasterDataController::class, 'deleteCategory'])->name('categories.delete');
        Route::get('/siswa', [MasterDataController::class, 'students'])->name('students');
        Route::get('/siswa/export.xlsx', [MasterDataController::class, 'exportStudentsExcel'])->name('students.xlsx');
        Route::get('/siswa/export.pdf', [MasterDataController::class, 'exportStudentsPdf'])->name('students.pdf');
        Route::post('/siswa', [MasterDataController::class, 'storeStudent'])->name('students.store');
        Route::put('/siswa/{user}', [MasterDataController::class, 'updateStudent'])->name('students.update');
        Route::delete('/siswa/{user}', [MasterDataController::class, 'deleteStudent'])->name('students.delete');
        Route::get('/verifikasi', [LoanController::class, 'approvals'])->name('approvals');
        Route::post('/verifikasi/{peminjaman}/setujui', [LoanController::class, 'approve'])->name('approvals.approve');
        Route::post('/verifikasi/{peminjaman}/tolak', [LoanController::class, 'reject'])->name('approvals.reject');
        Route::get('/pengembalian', [ReturnController::class, 'index'])->name('returns');
        Route::get('/pengembalian/verifikasi', [ReturnController::class, 'verificationIndex'])->name('returns.verify');
        Route::post('/pengembalian/verifikasi/{return}/setujui', [ReturnController::class, 'approveReturn'])->name('returns.verify.approve');
        Route::get('/pengembalian/cari', [ReturnController::class, 'search'])->name('returns.search');
        Route::post('/pengembalian', [ReturnController::class, 'store'])->name('returns.store');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports');
        Route::get('/laporan/export.xlsx', [ReportController::class, 'excel'])->name('reports.xlsx');
        Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });

    Route::prefix('teknisi')->name('technician.')->middleware('role:teknisi')->group(function () {
        Route::get('/verifikasi', [TechnicianController::class, 'approvals'])->name('approvals');
        Route::post('/verifikasi/{peminjaman}/setujui', [TechnicianController::class, 'approve'])->name('approvals.approve');
        Route::get('/penyerahan', [TechnicianController::class, 'handovers'])->name('handovers');
        Route::post('/penyerahan/{peminjaman}', [TechnicianController::class, 'storeHandover'])->name('handovers.store');
        Route::get('/pengembalian', [ReturnController::class, 'index'])->name('returns');
        Route::get('/pengembalian/cari', [ReturnController::class, 'search'])->name('returns.search');
        Route::post('/pengembalian', [ReturnController::class, 'store'])->name('returns.store');
    });
});
