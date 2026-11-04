<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengembalian extends Model
{
    protected $table = 'detail_pengembalian';

    protected $primaryKey = 'id_detail_pengembalian';

    protected $fillable = ['id_detail', 'item_unit_id', 'jumlah', 'tanggal_kembali_aktual', 'kondisi_alat', 'foto_bukti', 'denda', 'catatan', 'id_teknisi'];

    protected $casts = ['jumlah' => 'integer', 'tanggal_kembali_aktual' => 'datetime', 'denda' => 'decimal:2'];

    public function detailPeminjaman(): BelongsTo
    {
        return $this->belongsTo(DetailPeminjaman::class, 'id_detail', 'id_detail');
    }

    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id', 'item_unit_id');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_teknisi', 'id_user');
    }
}
