<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';

    protected $primaryKey = 'id_peminjaman';

    protected $fillable = ['id_user', 'tanggal_pengajuan', 'tanggal_pinjam', 'tanggal_kembali_rencana', 'keperluan', 'status', 'catatan_admin'];

    protected $casts = ['tanggal_pengajuan' => 'datetime', 'tanggal_pinjam' => 'date', 'tanggal_kembali_rencana' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function pengembalian(): HasOne
    {
        return $this->hasOne(Pengembalian::class, 'id_peminjaman', 'id_peminjaman');
    }

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
}
