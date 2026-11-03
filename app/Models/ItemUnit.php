<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItemUnit extends Model
{
    protected $table = 'item_units';
    protected $primaryKey = 'item_unit_id';
    protected $fillable = ['alat_id', 'asset_id', 'qr_code', 'condition', 'status'];

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'alat_id', 'id_alat');
    }

    public function details(): BelongsToMany
    {
        return $this->belongsToMany(DetailPeminjaman::class, 'detail_peminjaman_item_unit', 'item_unit_id', 'id_detail')
            ->withPivot(['assigned_by'])
            ->withTimestamps();
    }
}
