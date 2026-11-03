@extends('layouts.app')
@section('title', 'Katalog Alat')
@section('content')
<div class="page-head"><div><h1>Katalog Alat</h1><p>Temukan alat praktik yang tersedia di laboratorium RPL.</p></div><button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#scanModal"><i class="bi bi-upc-scan me-1"></i>Ajukan dengan kode alat</button></div>
<div class="row g-3">@forelse($equipment as $item)<div class="col-sm-6 col-xl-4"><article class="surface card-item h-100 d-flex flex-column"><img class="w-100 rounded mb-3" style="height:150px;object-fit:cover" src="{{ $item->foto ? asset('storage/'.$item->foto) : asset('assets/logo.png') }}" alt="Foto {{ $item->nama_alat }}" loading="lazy"><div class="d-flex align-items-start gap-3"><span class="equipment-icon"><i class="bi bi-tools"></i></span><div class="flex-grow-1"><span class="badge-status {{ strtolower($item->status_alat) === 'tersedia' ? 'status-tersedia' : 'status-'.strtolower($item->status_alat) }}">{{ $item->status_alat }}</span><h2 class="panel-title mt-2">{{ $item->nama_alat }}</h2><div class="panel-subtitle">{{ $item->kode_alat }} · {{ $item->kategori->nama_kategori }}</div></div></div><div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3"><span class="text-muted">Stok tersedia <strong class="text-dark">{{ $item->stok_tersedia }}</strong></span><button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#loanModal" data-equipment-id="{{ $item->id_alat }}" data-equipment-name="{{ $item->nama_alat }}" data-equipment-code="{{ $item->kode_alat }}" data-stock="{{ $item->stok_tersedia }}" @disabled($item->status_alat !== 'Tersedia' || $item->stok_tersedia < 1)>{{ $item->status_alat === 'Tersedia' && $item->stok_tersedia > 0 ? 'Pinjam' : 'Tidak tersedia' }}</button></div></article></div>
@empty<div class="col-12"><div class="surface p-5 text-center text-muted">Alat tidak ditemukan.</div></div>@endforelse</div>
<div class="mt-3">{{ $equipment->links() }}</div>
<div class="modal fade" id="loanModal" tabindex="-1" aria-labelledby="loanModalTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><form method="post" action="{{ route('loans.create') }}" id="loan-modal-form">@csrf<input type="hidden" name="items[0][id_alat]" id="modal-equipment-id"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold" id="loanModalTitle">Ajukan Peminjaman</h2><div class="text-muted" id="modal-equipment-meta"></div></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="alert alert-warning d-none" id="modal-loan-alert" role="alert"></div><div class="row g-3"><div class="col-12 col-sm-6"><label class="form-label" for="modal-quantity">Jumlah</label><input class="form-control" id="modal-quantity" type="number" name="items[0][jumlah]" min="1" required><div class="form-text" id="modal-stock-help"></div></div><div class="col-12 col-sm-6"><label class="form-label" for="modal-loan-date">Tanggal pinjam</label><input class="form-control" id="modal-loan-date" type="date" name="tanggal_pinjam" min="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div><div class="col-12"><label class="form-label" for="modal-return-date">Rencana kembali</label><input class="form-control" id="modal-return-date" type="date" name="tanggal_kembali_rencana" min="{{ now()->toDateString() }}" max="{{ now()->addDays(5)->toDateString() }}" value="{{ now()->addDays(5)->toDateString() }}" required></div><div class="col-12"><label class="form-label" for="modal-purpose">Keperluan</label><textarea class="form-control" id="modal-purpose" name="keperluan" rows="3" maxlength="2000" required placeholder="Jelaskan keperluan peminjaman alat..."></textarea></div></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="modal-submit" type="submit"><i class="bi bi-send me-1"></i>Kirim pengajuan</button></div></form></div></div></div>
<div class="modal fade" id="scanModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold">Cari alat dengan kode manual</h2><div class="text-muted">Masukkan kode alat, lalu lengkapi formulir pengajuan.</div></div><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
<form id="manual-equipment-lookup" class="input-group"><input class="form-control" id="manual-equipment-code" placeholder="Contoh: LAN-001" required><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari</button></form>
<div id="scan-result" class="mt-3 text-center text-muted"></div><form class="d-none surface p-3 mt-3" method="post" action="{{ route('loans.create') }}" id="scanned-loan-form">@csrf<input type="hidden" name="id_alat" id="scanned-equipment-id"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="panel-title">Form pengajuan alat</h3><div class="panel-subtitle" id="scanned-form-equipment"></div></div><span class="badge-status status-tersedia" id="scanned-form-stock"></span></div><div class="row g-3"><div class="col-6 col-md-3"><label class="form-label" for="scanned-quantity">Jumlah</label><input class="form-control" id="scanned-quantity" type="number" name="jumlah" min="1" value="1" required><div class="form-text text-danger d-none" id="scanned-stock-warning">Jumlah melebihi stok tersedia.</div></div><div class="col-6 col-md-4"><label class="form-label" for="scanned-loan-date">Tanggal pinjam</label><input class="form-control" id="scanned-loan-date" type="date" name="tanggal_pinjam" min="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div><div class="col-6 col-md-5"><label class="form-label" for="scanned-return-date">Rencana kembali</label><input class="form-control" id="scanned-return-date" type="date" name="tanggal_kembali_rencana" min="{{ now()->toDateString() }}" max="{{ now()->addDays(5)->toDateString() }}" value="{{ now()->addDays(5)->toDateString() }}" required></div><div class="col-12"><label class="form-label" for="scanned-purpose">Keperluan</label><textarea class="form-control" id="scanned-purpose" name="keperluan" rows="2" maxlength="2000" required placeholder="Jelaskan keperluan alat..."></textarea></div><div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" id="scanned-submit" type="submit"><i class="bi bi-send me-1"></i>Kirim pengajuan alat ini</button></div></div></form></div></div></div></div>
@endsection
@push('scripts')
<script>
const catalogLookupUrl = @json(route('catalog.lookup', ['token' => '__TOKEN__'], false));
const scannedLoanForm = document.getElementById('scanned-loan-form');
const scannedQuantity = document.getElementById('scanned-quantity');
const scannedSubmit = document.getElementById('scanned-submit');
const scannedWarning = document.getElementById('scanned-stock-warning');
async function showEquipment(token) {
    const result = document.getElementById('scan-result');
    scannedLoanForm.classList.add('d-none');
    try {
        const response = await fetch(catalogLookupUrl.replace('__TOKEN__', encodeURIComponent(token)), {
            credentials: 'same-origin',
            headers: {Accept: 'application/json'},
        });
        if (response.status === 401) throw new Error('Sesi login berakhir. Silakan masuk kembali.');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Alat tidak ditemukan.');
        const nameElement = document.createElement('strong');
        nameElement.textContent = data.nama;
        const detailsElement = document.createElement('div');
        detailsElement.textContent = `${data.kode} · ${data.kategori}`;
        const stockElement = document.createElement('div');
        stockElement.textContent = `Stok: ${data.stok} · ${data.status}`;
        result.replaceChildren(nameElement, detailsElement, stockElement);
        const canRequest = data.status === 'Tersedia' && Number(data.stok) > 0;
        scannedLoanForm.classList.toggle('d-none', !canRequest);
        if (canRequest) {
            document.getElementById('scanned-equipment-id').value = data.id_alat;
            document.getElementById('scanned-form-equipment').textContent = `${data.kode} · ${data.kategori}`;
            document.getElementById('scanned-form-stock').textContent = `Stok tersedia: ${data.stok}`;
            scannedQuantity.max = data.stok;
            scannedQuantity.value = 1;
            scannedQuantity.classList.remove('is-invalid');
            scannedWarning.classList.add('d-none');
            scannedSubmit.disabled = false;
        } else {
            const availabilityMessage = document.createElement('div');
            availabilityMessage.className = 'alert alert-warning mt-2 mb-0';
            availabilityMessage.textContent = 'Form pengajuan belum tersedia karena alat tidak memiliki stok yang dapat dipinjam.';
            result.append(availabilityMessage);
        }
    } catch (error) {
        result.textContent = error instanceof Error ? error.message : 'Alat tidak dapat dicari.';
    }
}
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
    modalAlert.textContent = quantity > modalStock
        ? `Jumlah melebihi stok yang tersedia (${modalStock}). Kurangi jumlah sebelum mengirim pengajuan.`
        : invalid ? 'Masukkan jumlah minimal 1.' : '';
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
loanModalForm.addEventListener('submit', event => {
    if (!validateModalQuantity()) event.preventDefault();
});
scannedLoanForm.addEventListener('submit', event => {
    if (Number(scannedQuantity.value) > Number(scannedQuantity.max)) {
        event.preventDefault();
        scannedQuantity.classList.add('is-invalid');
        scannedWarning.classList.remove('d-none');
        scannedSubmit.disabled = true;
        const alert = document.createElement('div');
        alert.className = 'alert alert-warning mt-2 mb-0';
        alert.textContent = `Jumlah melebihi stok yang tersedia (${scannedQuantity.max}). Kurangi jumlah sebelum mengirim pengajuan.`;
        document.getElementById('scan-result').append(alert);
    }
});
scannedQuantity.addEventListener('input', () => {
    const invalid = Number(scannedQuantity.value) < 1 || Number(scannedQuantity.value) > Number(scannedQuantity.max);
    scannedQuantity.classList.toggle('is-invalid', invalid);
    scannedWarning.classList.toggle('d-none', !invalid);
    scannedSubmit.disabled = invalid;
});
document.getElementById('manual-equipment-lookup').addEventListener('submit', event => {
    event.preventDefault();
    showEquipment(document.getElementById('manual-equipment-code').value.trim());
});
</script>
@endpush
