<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenyerahan extends Model
{
    protected $table = 'detail_penyerahan';
    protected $primaryKey = 'id_penyerahan';
    protected $fillable = ['id_detail', 'id_teknisi', 'jumlah', 'tanggal_penyerahan', 'kondisi_alat', 'foto_bukti', 'catatan'];
    protected $casts = ['tanggal_penyerahan' => 'datetime', 'jumlah' => 'integer'];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(DetailPeminjaman::class, 'id_detail', 'id_detail');
    }
}
