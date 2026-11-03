<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_penyerahan', function (Blueprint $table): void {
            $table->string('foto_bukti')->nullable()->after('kondisi_alat');
        });

        Schema::table('detail_pengembalian', function (Blueprint $table): void {
            $table->string('foto_bukti')->nullable()->after('kondisi_alat');
        });
    }

    public function down(): void
    {
        Schema::table('detail_penyerahan', function (Blueprint $table): void {
            $table->dropColumn('foto_bukti');
        });

        Schema::table('detail_pengembalian', function (Blueprint $table): void {
            $table->dropColumn('foto_bukti');
        });
    }
};
