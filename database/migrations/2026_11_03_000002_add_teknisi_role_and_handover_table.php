<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'siswa', 'teknisi') NOT NULL DEFAULT 'siswa'");
        }

        Schema::create('detail_penyerahan', function (Blueprint $table): void {
            $table->id('id_penyerahan');
            $table->foreignId('id_detail')->constrained('detail_peminjaman', 'id_detail')->restrictOnDelete();
            $table->foreignId('id_teknisi')->constrained('users', 'id_user')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamp('tanggal_penyerahan');
            $table->enum('kondisi_alat', ['Baik', 'Rusak']);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['id_detail', 'tanggal_penyerahan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penyerahan');
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'siswa') NOT NULL DEFAULT 'siswa'");
        }
    }
};
