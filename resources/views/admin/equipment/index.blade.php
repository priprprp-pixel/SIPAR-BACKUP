@extends('layouts.app')
@section('title', 'Master Data Alat')
@section('content')
<div class="page-head"><div><h1>Master Data Alat</h1><p>Kelola inventaris, stok, dan kondisi alat.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipment"><i class="bi bi-plus-lg me-1"></i>Tambah alat</button></div>
<form method="get" class="surface p-2 mb-3 d-flex gap-2"><input class="form-control border-0" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode alat..."><button class="btn btn-primary"><i class="bi bi-search"></i></button></form>
<section class="surface"><div class="table-responsive"><table class="table"><thead><tr><th>Nama Alat</th><th>Kode</th><th>Kategori</th><th>Stok</th><th>Kondisi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($items as $item)<tr><td><div class="d-flex align-items-center gap-2"><span class="equipment-icon overflow-hidden"><x-equipment-image :equipment="$item" alt="Foto {{ $item->nama_alat }}"></x-equipment-image></span><strong>{{ $item->nama_alat }}</strong></div></td><td>{{ $item->kode_alat }}</td><td>{{ $item->kategori->nama_kategori }}</td><td>{{ $item->stok_tersedia }} / {{ $item->stok_total }}</td><td>{{ $item->kondisi }}</td><td><span class="badge-status status-{{ strtolower($item->status_alat) }}">{{ $item->status_alat }}</span></td><td><div class="d-flex gap-1"><button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#edit-{{ $item->id_alat }}" title="Edit"><i class="bi bi-pencil"></i></button><form method="post" action="{{ route('admin.equipment.delete',$item) }}" onsubmit="return confirm('Hapus alat ini?')">@csrf @method('DELETE')<button class="btn btn-light btn-sm text-danger" title="Hapus"><i class="bi bi-trash"></i></button></form></div></td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data alat.</td></tr>@endforelse
</tbody></table></div><div class="p-3">{{ $items->links() }}</div></section>
@foreach($items as $item)
<div class="modal fade" id="edit-{{ $item->id_alat }}" tabindex="-1" aria-labelledby="edit-title-{{ $item->id_alat }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered equipment-modal-dialog"><form class="modal-content" method="post" enctype="multipart/form-data" action="{{ route('admin.equipment.update',$item) }}">@csrf @method('PUT')<div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="edit-title-{{ $item->id_alat }}">Ubah data alat</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">@include('admin.equipment.fields',['item'=>$item])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan perubahan</button></div></form></div></div>
@endforeach
<div class="modal fade" id="addEquipment" tabindex="-1" aria-labelledby="add-equipment-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered equipment-modal-dialog"><form class="modal-content" method="post" enctype="multipart/form-data" action="{{ route('admin.equipment.store') }}">@csrf<div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="add-equipment-title">Tambah alat</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">@include('admin.equipment.fields',['item'=>null])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan alat</button></div></form></div></div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-equipment-photo-input]').forEach((input) => {
    const field = input.closest('.col-12');
    const preview = field.querySelector('[data-equipment-photo-preview]');
    const image = preview.querySelector('img');
    let previewUrl = null;

    const resetPreview = () => {
        input.value = '';
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        const originalSrc = image.dataset.originalSrc;
        if (originalSrc) {
            image.src = originalSrc;
            image.hidden = false;
            preview.hidden = false;
        } else {
            image.removeAttribute('src');
            image.hidden = true;
            preview.hidden = true;
        }
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        image.hidden = false;
        preview.hidden = false;
    });

    input.closest('.modal').addEventListener('hidden.bs.modal', resetPreview);
});
</script>
@endpush
