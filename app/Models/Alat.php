<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alat extends Model
{
    protected $table = 'alat';

    protected $primaryKey = 'id_alat';

    protected $fillable = ['kode_alat', 'nama_alat', 'id_kategori', 'stok_total', 'stok_tersedia', 'stok_maintenance', 'stok_rusak', 'kondisi', 'deskripsi', 'qr_code', 'status_alat'];

    protected $casts = ['stok_total' => 'integer', 'stok_tersedia' => 'integer', 'stok_maintenance' => 'integer', 'stok_rusak' => 'integer'];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriAlat::class, 'id_kategori', 'id_kategori');
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_alat', 'id_alat');
    }

    public function itemUnits(): HasMany
    {
        return $this->hasMany(ItemUnit::class, 'alat_id', 'id_alat');
    }

    public function syncStatusFromStock(): void
    {
        $borrowed = max(0, $this->stok_total - $this->stok_tersedia - $this->stok_maintenance - $this->stok_rusak);
        $this->status_alat = match (true) {
            $this->stok_tersedia > 0 => 'Tersedia',
            $borrowed > 0 => 'Dipinjam',
            $this->stok_maintenance > 0 => 'Maintenance',
            $this->stok_rusak > 0 => 'Rusak',
            default => 'Rusak',
        };
    }
}
