@extends('layouts.app')
@section('title', 'Katalog Alat')
@section('content')
<div class="page-head"><div><h1>Katalog Alat</h1><p>Cari alat berdasarkan nama atau kode inventaris.</p></div></div>
<form method="get" class="surface p-2 mb-3 d-flex gap-2" id="catalog-search"><input class="form-control border-0" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode alat..." autocomplete="off"><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button></form>
<div class="row g-3">@forelse($equipment as $item)<div class="col-sm-6 col-xl-4"><article class="surface card-item h-100 d-flex flex-column"><x-equipment-image :equipment="$item" class="w-100 rounded mb-3" style="height:150px;object-fit:cover" alt="Foto {{ $item->nama_alat }}"></x-equipment-image><div class="d-flex align-items-start gap-3"><span class="equipment-icon overflow-hidden"><x-equipment-image :equipment="$item" alt="Foto {{ $item->nama_alat }}"></x-equipment-image></span><div class="flex-grow-1"><h2 class="panel-title">{{ $item->nama_alat }}</h2><div class="panel-subtitle">{{ $item->kode_alat }} · {{ $item->kategori->nama_kategori }}</div></div></div><div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3"><span class="text-muted">Stok <strong class="text-dark">{{ $item->stok_tersedia }}</strong></span><div class="d-flex align-items-center gap-2"><span class="badge-status {{ strtolower($item->status_alat) === 'tersedia' ? 'status-tersedia' : 'status-'.strtolower($item->status_alat) }}">{{ $item->status_alat }}</span><button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#loanModal" data-equipment-id="{{ $item->id_alat }}" data-equipment-name="{{ $item->nama_alat }}" data-equipment-code="{{ $item->kode_alat }}" data-stock="{{ $item->stok_tersedia }}" @disabled($item->status_alat !== 'Tersedia' || $item->stok_tersedia < 1)>{{ $item->status_alat === 'Tersedia' && $item->stok_tersedia > 0 ? 'Pinjam' : 'Tidak tersedia' }}</button></div></div></article></div>
@empty<div class="col-12"><div class="surface p-5 text-center text-muted">Alat tidak ditemukan.</div></div>@endforelse</div>
<div class="mt-3">{{ $equipment->links() }}</div>
<div class="modal fade" id="loanModal" tabindex="-1" aria-labelledby="loanModalTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="post" action="{{ route('loans.create') }}" id="loan-modal-form">@csrf<input type="hidden" name="items[0][id_alat]" id="modal-equipment-id"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold" id="loanModalTitle">Ajukan Peminjaman</h2><div class="text-muted" id="modal-equipment-meta"></div></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="alert alert-warning d-none" id="modal-loan-alert" role="alert"></div><div class="row g-3"><div class="col-12 col-sm-6"><label class="form-label" for="modal-quantity">Jumlah</label><input class="form-control" id="modal-quantity" type="number" name="items[0][jumlah]" min="1" required><div class="form-text" id="modal-stock-help"></div></div><div class="col-12 col-sm-6"><label class="form-label" for="modal-loan-date">Tanggal pinjam</label><input class="form-control" id="modal-loan-date" type="date" name="tanggal_pinjam" min="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div><div class="col-12"><label class="form-label" for="modal-return-date">Rencana kembali</label><input class="form-control" id="modal-return-date" type="date" name="tanggal_kembali_rencana" min="{{ now()->toDateString() }}" max="{{ now()->addDays(5)->toDateString() }}" value="{{ now()->addDays(5)->toDateString() }}" required></div><div class="col-12"><label class="form-label" for="modal-purpose">Keperluan</label><textarea class="form-control" id="modal-purpose" name="keperluan" rows="3" maxlength="2000" required placeholder="Jelaskan keperluan alat..."></textarea></div></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="modal-submit" type="submit"><i class="bi bi-send me-1"></i>Kirim pengajuan</button></div></form></div></div></div>
@endsection
@push('scripts')
<script>
const loanModal = document.getElementById('loanModal');
const loanModalForm = document.getElementById('loan-modal-form');
const modalQuantity = document.getElementById('modal-quantity');
const modalAlert = document.getElementById('modal-loan-alert');
const modalSubmit = document.getElementById('modal-submit');
let modalStock = 0;
function validateModalQuantity() {
    const quantity = Number(modalQuantity.value);
    const invalid = !Number.isInteger(quantity) || quantity < 1 || quantity > modalStock;
    modalQuantity.classList.toggle('is-invalid', invalid);
    modalAlert.textContent = quantity > modalStock ? `Jumlah melebihi stok tersedia (${modalStock}).` : invalid ? 'Masukkan jumlah minimal 1.' : '';
    modalAlert.classList.toggle('d-none', !invalid);
    modalSubmit.disabled = invalid;
    return !invalid;
}
loanModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    modalStock = Number(button.dataset.stock);
    document.getElementById('modal-equipment-id').value = button.dataset.equipmentId;
    document.getElementById('modal-equipment-meta').textContent = `${button.dataset.equipmentCode} · ${button.dataset.equipmentName}`;
    document.getElementById('modal-stock-help').textContent = `Stok tersedia: ${modalStock} unit`;
    modalQuantity.max = modalStock;
    modalQuantity.value = 1;
    modalAlert.classList.add('d-none');
    modalQuantity.classList.remove('is-invalid');
    modalSubmit.disabled = false;
});
modalQuantity.addEventListener('input', validateModalQuantity);
loanModalForm.addEventListener('submit', event => { if (!validateModalQuantity()) event.preventDefault(); });
let catalogSearchTimer;
document.querySelector('#catalog-search input[name="q"]').addEventListener('input', event => {
    clearTimeout(catalogSearchTimer);
    catalogSearchTimer = setTimeout(() => document.getElementById('catalog-search').requestSubmit(), 350);
});
</script>
@endpush
