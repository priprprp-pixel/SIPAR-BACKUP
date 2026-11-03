@extends('layouts.app')
@section('title', 'Katalog Alat')
@section('content')
<div class="page-head"><div><h1>Katalog Alat</h1><p>Temukan alat praktik yang tersedia di laboratorium RPL.</p></div><button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#scanModal"><i class="bi bi-search me-1"></i>Cari kode alat</button></div>
<form method="get" class="surface p-2 mb-3 d-flex gap-2 catalog-search-form"><input class="form-control border-0" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode alat..."><button class="btn btn-primary" type="submit" aria-label="Cari alat"><i class="bi bi-search"></i></button></form>
<form method="post" action="{{ route('loans.create') }}" id="loan-request-form">@csrf
<div class="row g-3">@forelse($equipment as $item)<div class="col-sm-6 col-xl-4"><article class="surface card-item h-100"><div class="d-flex align-items-start gap-3"><span class="equipment-icon"><i class="bi bi-tools"></i></span><div class="flex-grow-1"><span class="badge-status {{ strtolower($item->status_alat) === 'tersedia' ? 'status-tersedia' : 'status-'.strtolower($item->status_alat) }}">{{ $item->status_alat }}</span><h2 class="panel-title mt-2">{{ $item->nama_alat }}</h2><div class="panel-subtitle">{{ $item->kode_alat }} · {{ $item->kategori->nama_kategori }}</div></div></div><div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3"><label class="d-flex align-items-center gap-2 text-muted"><input class="form-check-input equipment-select m-0" type="checkbox" name="items[{{ $item->id_alat }}][id_alat]" value="{{ $item->id_alat }}" data-equipment="{{ $item->id_alat }}" @checked(old('items.'.$item->id_alat.'.id_alat')) @disabled($item->status_alat !== 'Tersedia' || $item->stok_tersedia < 1)> Pilih · stok <strong class="text-dark">{{ $item->stok_tersedia }}</strong></label><input class="form-control equipment-quantity" style="width:82px" type="number" name="items[{{ $item->id_alat }}][jumlah]" value="{{ old('items.'.$item->id_alat.'.jumlah',1) }}" min="1" max="{{ $item->stok_tersedia }}" aria-label="Jumlah {{ $item->nama_alat }}" @disabled(!$item->stok_tersedia || !old('items.'.$item->id_alat.'.id_alat'))></div></article></div>
@empty<div class="col-12"><div class="surface p-5 text-center text-muted">Alat tidak ditemukan.</div></div>@endforelse</div>
@if($equipment->count())<section class="surface p-3 p-md-4 mt-3"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="panel-title">Detail pengajuan</h2><div class="panel-subtitle">Pilih satu atau beberapa alat di atas.</div></div><span class="badge-status status-dipinjam" id="selected-count">0 alat dipilih</span></div><div class="row g-3"><div class="col-6 col-md-3"><label class="form-label">Tanggal pinjam</label><input class="form-control" type="date" name="tanggal_pinjam" min="{{ now()->toDateString() }}" value="{{ old('tanggal_pinjam') }}" required></div><div class="col-6 col-md-3"><label class="form-label">Rencana kembali</label><input class="form-control" type="date" name="tanggal_kembali_rencana" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali_rencana') }}" required></div><div class="col-12 col-md-6"><label class="form-label">Keperluan</label><textarea class="form-control" name="keperluan" rows="2" required placeholder="Jelaskan keperluan alat praktik...">{{ old('keperluan') }}</textarea></div><div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Kirim pengajuan</button></div></div></section>@endif
<div class="alert alert-warning d-none mt-3" id="loan-limit-alert" role="alert"></div><div class="mt-3">{{ $equipment->links() }}</div></form>
<div class="modal fade" id="scanModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold">Cari alat dengan kode manual</h2><div class="text-muted">Masukkan kode alat, lalu lengkapi formulir pengajuan.</div></div><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
<form id="manual-equipment-lookup" class="input-group"><input class="form-control" id="manual-equipment-code" placeholder="Contoh: LAN-001" required><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari</button></form>
<div id="scan-result" class="mt-3 text-center text-muted"></div><form class="d-none surface p-3 mt-3" method="post" action="{{ route('loans.create') }}" id="scanned-loan-form">@csrf<input type="hidden" name="id_alat" id="scanned-equipment-id"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="panel-title">Form pengajuan alat</h3><div class="panel-subtitle" id="scanned-form-equipment"></div></div><span class="badge-status status-tersedia" id="scanned-form-stock"></span></div><div class="row g-3"><div class="col-6 col-md-3"><label class="form-label" for="scanned-quantity">Jumlah</label><input class="form-control" id="scanned-quantity" type="number" name="jumlah" min="1" value="1" required></div><div class="col-6 col-md-4"><label class="form-label" for="scanned-loan-date">Tanggal pinjam</label><input class="form-control" id="scanned-loan-date" type="date" name="tanggal_pinjam" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div><div class="col-6 col-md-5"><label class="form-label" for="scanned-return-date">Rencana kembali</label><input class="form-control" id="scanned-return-date" type="date" name="tanggal_kembali_rencana" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div><div class="col-12"><label class="form-label" for="scanned-purpose">Keperluan</label><textarea class="form-control" id="scanned-purpose" name="keperluan" rows="2" maxlength="2000" required placeholder="Jelaskan keperluan peminjaman alat..."></textarea></div><div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Kirim pengajuan alat ini</button></div></div></form></div></div></div></div>
@endsection
@push('scripts')
<script>
const catalogLookupUrl = @json(route('catalog.lookup', ['token' => '__TOKEN__'], false));
const scannedLoanForm = document.getElementById('scanned-loan-form');
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
            const quantity = document.getElementById('scanned-quantity');
            quantity.max = data.stok;
            quantity.value = 1;
        } else {
            const availabilityMessage = document.createElement('div');
            availabilityMessage.className = 'alert alert-warning mt-2 mb-0';
            availabilityMessage.textContent = 'Form pengajuan belum tersedia karena alat tidak memiliki stok yang dapat dipinjam.';
            result.append(availabilityMessage);
        }
        const selection = document.querySelector(`.equipment-select[data-equipment="${data.id_alat}"]`);
        if (selection && !selection.disabled) {
            selection.checked = true;
            selection.dispatchEvent(new Event('change'));
        }
    } catch (error) {
        result.textContent = error instanceof Error ? error.message : 'Alat tidak dapat dicari.';
    }
}
function updateSelectionCount() {
    const count = [...document.querySelectorAll('.equipment-select:checked')].length;
    document.getElementById('selected-count').textContent = `${count} alat dipilih`;
}
function validateLoanQuantities() {
    let invalid = false;
    document.querySelectorAll('.equipment-select:checked').forEach(checkbox => {
        const quantity = checkbox.closest('article').querySelector('.equipment-quantity');
        if (Number(quantity.value) > Number(quantity.max)) {
            invalid = true;
            quantity.classList.add('is-invalid');
        } else {
            quantity.classList.remove('is-invalid');
        }
    });
    const alert = document.getElementById('loan-limit-alert');
    alert.textContent = invalid ? 'Jumlah peminjaman melebihi stok yang tersedia. Kurangi jumlah alat sebelum mengirim pengajuan.' : '';
    alert.classList.toggle('d-none', !invalid);
    return !invalid;
}
document.querySelectorAll('.equipment-select').forEach(checkbox => {
    const quantity = checkbox.closest('article').querySelector('.equipment-quantity');
    checkbox.addEventListener('change', () => { quantity.disabled = !checkbox.checked; updateSelectionCount(); validateLoanQuantities(); });
    quantity.addEventListener('input', validateLoanQuantities);
});
updateSelectionCount();
document.getElementById('loan-request-form').addEventListener('submit', event => {
    if (!validateLoanQuantities()) event.preventDefault();
});
scannedLoanForm.addEventListener('submit', event => {
    const quantity = document.getElementById('scanned-quantity');
    if (Number(quantity.value) > Number(quantity.max)) {
        event.preventDefault();
        const alert = document.createElement('div');
        alert.className = 'alert alert-warning mt-2 mb-0';
        alert.textContent = `Jumlah melebihi stok yang tersedia (${quantity.max}). Kurangi jumlah sebelum mengirim pengajuan.`;
        document.getElementById('scan-result').append(alert);
    }
});
document.getElementById('manual-equipment-lookup').addEventListener('submit', event => {
    event.preventDefault();
    showEquipment(document.getElementById('manual-equipment-code').value.trim());
});
</script>
@endpush
