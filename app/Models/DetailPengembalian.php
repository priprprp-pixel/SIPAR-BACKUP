<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengembalian extends Model
{
    protected $table = 'detail_pengembalian';

    protected $primaryKey = 'id_detail_pengembalian';

    protected $fillable = ['id_detail', 'jumlah', 'tanggal_kembali_aktual', 'kondisi_alat', 'denda', 'catatan'];

    protected $casts = ['jumlah' => 'integer', 'tanggal_kembali_aktual' => 'datetime', 'denda' => 'decimal:2'];

    public function detailPeminjaman(): BelongsTo
    {
        return $this->belongsTo(DetailPeminjaman::class, 'id_detail', 'id_detail');
    }
}
