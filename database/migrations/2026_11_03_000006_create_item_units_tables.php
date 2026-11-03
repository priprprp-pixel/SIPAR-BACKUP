<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table): void {
            $table->id('item_unit_id');
            $table->foreignId('alat_id')->constrained('alat', 'id_alat')->restrictOnDelete();
            $table->string('asset_id', 80)->unique();
            $table->string('qr_code', 120)->unique();
            $table->enum('condition', ['Baik', 'Maintenance', 'Rusak'])->default('Baik');
            $table->enum('status', ['Tersedia', 'Dipinjam'])->default('Tersedia');
            $table->timestamps();
            $table->index(['alat_id', 'condition', 'status']);
        });

        Schema::create('detail_peminjaman_item_unit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_detail')->constrained('detail_peminjaman', 'id_detail')->cascadeOnDelete();
            $table->foreignId('item_unit_id')->constrained('item_units', 'item_unit_id')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamps();
            $table->unique(['id_detail', 'item_unit_id']);
            $table->unique('item_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman_item_unit');
        Schema::dropIfExists('item_units');
    }
};
