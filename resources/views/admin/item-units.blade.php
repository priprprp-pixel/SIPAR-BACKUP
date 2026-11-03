@extends('layouts.app')
@section('title', 'Unit Fisik Alat')
@section('content')
<div class="page-head">
    <div><h1>Unit Fisik Alat</h1><p>Kelola Asset ID dan QR setiap unit inventaris.</p></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newItemUnit"><i class="bi bi-plus-lg me-1"></i>Tambah unit</button>
</div>
<form class="surface p-2 mb-3 d-flex gap-2" method="get">
    <input class="form-control border-0" name="q" value="{{ request('q') }}" placeholder="Cari Asset ID atau nama alat...">
    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
</form>
<section class="surface"><div class="table-responsive"><table class="table">
    <thead><tr><th>Asset ID</th><th>Nama Alat</th><th>Kode Alat</th><th>Kondisi</th><th>Status</th><th>QR Code</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($units as $unit)
        <tr>
            <td><strong>{{ $unit->asset_id }}</strong></td><td>{{ $unit->alat->nama_alat }}</td><td>{{ $unit->alat->kode_alat }}</td>
            <td><span class="badge-status status-{{ strtolower($unit->condition) }}">{{ $unit->condition }}</span></td>
            <td><span class="badge-status status-{{ strtolower($unit->status) }}">{{ $unit->status }}</span></td>
            <td><code>{{ $unit->qr_code }}</code></td>
            <td><div class="d-flex gap-1">
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editUnit-{{ $unit->item_unit_id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="post" action="{{ route('admin.item-units.delete', $unit) }}" onsubmit="return confirm('Hapus unit fisik ini?')">@csrf @method('DELETE')<button class="btn btn-light btn-sm text-danger" title="Hapus"><i class="bi bi-trash"></i></button></form>
            </div></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada unit fisik.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="p-3">{{ $units->links() }}</div></section>
@foreach($units as $unit)
<div class="modal fade" id="editUnit-{{ $unit->item_unit_id }}" tabindex="-1"><div class="modal-dialog modal-dialog-centered equipment-modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.item-units.update', $unit) }}">@csrf @method('PUT')
    <div class="modal-header"><h2 class="modal-title fs-6 fw-bold">Ubah Unit Fisik</h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">@include('admin.item-units-fields', ['unit' => $unit])</div>
    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
</form></div></div>
@endforeach
<div class="modal fade" id="newItemUnit" tabindex="-1"><div class="modal-dialog modal-dialog-centered equipment-modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.item-units.store') }}">@csrf
    <div class="modal-header"><h2 class="modal-title fs-6 fw-bold">Tambah Unit Fisik</h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">@include('admin.item-units-fields', ['unit' => null])</div>
    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan unit</button></div>
</form></div></div>
@endsection
