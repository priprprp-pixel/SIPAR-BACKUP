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
            DB::statement("ALTER TABLE peminjaman MODIFY status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Selesai') NOT NULL DEFAULT 'Pending'");
        }

        if (! Schema::hasColumn('detail_penyerahan', 'item_unit_id')) {
            Schema::table('detail_penyerahan', function (Blueprint $table): void {
                $table->foreignId('item_unit_id')->nullable()->after('id_detail')
                    ->constrained('item_units', 'item_unit_id')->restrictOnDelete();
                $table->index(['id_detail', 'item_unit_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('detail_penyerahan', 'item_unit_id')) {
            Schema::table('detail_penyerahan', function (Blueprint $table): void {
                $table->dropForeign(['item_unit_id']);
                $table->dropIndex(['id_detail', 'item_unit_id']);
                $table->dropColumn('item_unit_id');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE peminjaman MODIFY status ENUM('Pending', 'Approved', 'Rejected', 'Selesai') NOT NULL DEFAULT 'Pending'");
        }
    }
};
