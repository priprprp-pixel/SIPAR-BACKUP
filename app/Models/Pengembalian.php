<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Pengembalian extends Model
{
    protected $table = 'pengembalian';

    protected $primaryKey = 'id_pengembalian';

    protected $fillable = ['id_peminjaman', 'tanggal_kembali_aktual', 'kondisi_alat', 'denda', 'catatan'];

    protected $casts = ['tanggal_kembali_aktual' => 'datetime', 'denda' => 'decimal:2'];

    public function detailPengembalian(): HasManyThrough
    {
        return $this->hasManyThrough(
            DetailPengembalian::class,
            DetailPeminjaman::class,
            'id_peminjaman',
            'id_detail',
            'id_peminjaman',
            'id_detail'
        );
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }
}
