<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_alat', function (Blueprint $table) {
            $table->id('id_kategori');
            $table->string('nama_kategori')->unique();
            $table->timestamps();
        });

        Schema::create('alat', function (Blueprint $table) {
            $table->id('id_alat');
            $table->string('kode_alat')->unique();
            $table->string('nama_alat');
            $table->foreignId('id_kategori')->constrained('kategori_alat', 'id_kategori')->restrictOnDelete();
            $table->unsignedInteger('stok_total');
            $table->unsignedInteger('stok_tersedia');
            $table->string('kondisi')->default('Baik');
            $table->text('deskripsi')->nullable();
            $table->string('qr_code')->unique();
            $table->enum('status_alat', ['Tersedia', 'Dipinjam', 'Maintenance', 'Rusak'])->default('Tersedia');
            $table->timestamps();
        });

        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('id_peminjaman');
            $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->date('tanggal_pinjam');
            $table->date('tanggal_kembali_rencana');
            $table->text('keperluan');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Selesai'])->default('Pending');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_peminjaman', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_peminjaman')->constrained('peminjaman', 'id_peminjaman')->cascadeOnDelete();
            $table->foreignId('id_alat')->constrained('alat', 'id_alat')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamps();
            $table->unique(['id_peminjaman', 'id_alat']);
        });

        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id('id_pengembalian');
            $table->foreignId('id_peminjaman')->unique()->constrained('peminjaman', 'id_peminjaman')->cascadeOnDelete();
            $table->timestamp('tanggal_kembali_aktual')->useCurrent();
            $table->enum('kondisi_alat', ['Baik', 'Rusak', 'Hilang']);
            $table->decimal('denda', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian');
        Schema::dropIfExists('detail_peminjaman');
        Schema::dropIfExists('peminjaman');
        Schema::dropIfExists('alat');
        Schema::dropIfExists('kategori_alat');
    }
};
