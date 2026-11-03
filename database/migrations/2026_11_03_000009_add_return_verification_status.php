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
            DB::statement("ALTER TABLE detail_pengembalian MODIFY kondisi_alat ENUM('Baik', 'Maintenance', 'Rusak', 'Hilang') NOT NULL");
        }

        Schema::table('detail_pengembalian', function (Blueprint $table): void {
            $table->enum('status_verifikasi', ['Pending', 'Approved'])->default('Approved')->after('catatan');
            $table->foreignId('item_unit_id')->nullable()->after('id_detail')
                ->constrained('item_units', 'item_unit_id')->restrictOnDelete();
            $table->foreignId('id_teknisi')->nullable()->after('status_verifikasi')
                ->constrained('users', 'id_user')->nullOnDelete();
            $table->index(['status_verifikasi', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('detail_pengembalian', function (Blueprint $table): void {
            $table->dropForeign(['item_unit_id']);
            $table->dropForeign(['id_teknisi']);
            $table->dropIndex(['status_verifikasi', 'created_at']);
            $table->dropColumn(['status_verifikasi', 'item_unit_id', 'id_teknisi']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE detail_pengembalian MODIFY kondisi_alat ENUM('Baik', 'Rusak', 'Hilang') NOT NULL");
        }
    }
};
