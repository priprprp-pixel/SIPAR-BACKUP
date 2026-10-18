<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pengembalian', function (Blueprint $table): void {
            $table->id('id_detail_pengembalian');
            $table->foreignId('id_detail')->constrained('detail_peminjaman', 'id_detail')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamp('tanggal_kembali_aktual');
            $table->enum('kondisi_alat', ['Baik', 'Rusak', 'Hilang']);
            $table->decimal('denda', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['id_detail', 'tanggal_kembali_aktual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pengembalian');
    }
};
