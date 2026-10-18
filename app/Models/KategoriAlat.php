<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriAlat extends Model
{
    protected $table = 'kategori_alat';

    protected $primaryKey = 'id_kategori';

    protected $fillable = ['nama_kategori'];

    public function alat(): HasMany
    {
        return $this->hasMany(Alat::class, 'id_kategori', 'id_kategori');
    }
}
