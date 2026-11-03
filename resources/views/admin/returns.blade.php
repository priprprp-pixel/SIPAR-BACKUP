@extends('layouts.app')
@section('title','Pengembalian Alat')
@section('content')
<div class="page-head"><div><h1>Proses Pengembalian</h1><p>Cari alat berdasarkan kode atau nama untuk menemukan transaksi aktif.</p></div></div>
@php($returnRoutePrefix = auth()->user()->role === 'teknisi' ? 'technician' : 'admin')
<section class="surface p-3 p-md-4 mb-3"><div class="d-flex justify-content-between align-items-center"><div><h2 class="panel-title">Penerimaan Kembali Alat</h2><div class="panel-subtitle">Cari transaksi berdasarkan kode atau nama alat.</div></div><span class="badge-status status-dipinjam">{{ $activeLoans->total() }} transaksi</span></div><form class="row g-2 mt-3" id="manual-lookup"><div class="col"><label class="form-label" for="manual-token">Kode atau nama alat</label><input class="form-control" id="manual-token" placeholder="Contoh: LAN-001 atau Arduino" autocomplete="off" required></div><div class="col-auto align-self-end"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari</button></div></form></section>
<section class="surface"><div class="table-responsive"><table class="table"><thead><tr><th>Transaksi</th><th>Peminjam</th><th>Kode alat</th><th>Nama alat</th><th>Rencana Kembali</th><th>Status</th></tr></thead><tbody>@forelse($activeLoans as $loan)<tr><td>PJM-{{ str_pad($loan->id_peminjaman,5,'0',STR_PAD_LEFT) }}</td><td>{{ $loan->user->nama }}<small class="d-block text-muted">{{ $loan->user->nis_nip }}</small></td><td>{{ $loan->detail->map(fn($d)=>$d->alat->kode_alat)->join(', ') }}</td><td>{{ $loan->detail->map(fn($d)=>$d->alat->nama_alat.' · sisa '.max(0,$d->jumlah-$d->pengembalianDetail->sum('jumlah')).'/'.$d->jumlah)->join(', ') }}</td><td>{{ $loan->tanggal_kembali_rencana->format('d M Y') }}</td><td><span class="badge-status status-approved">Dipinjam</span></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada transaksi aktif.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $activeLoans->links() }}</div></section>
@php($technicianPhotoRequired = auth()->user()->role === 'teknisi')
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable operational-modal"><div class="modal-content"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold">Penerimaan Kembali Alat</h2><div class="text-muted" style="font-size:11px">Periksa alat berdasarkan kode inventaris.</div></div><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div id="return-error" class="text-danger text-center mt-2" style="font-size:12px"></div><div id="return-result" class="d-none mt-3"><div class="alert alert-info py-2 mb-2" id="equipment-result"></div><div id="no-active-return" class="alert alert-warning d-none mb-0">Alat ditemukan, tetapi tidak ada sisa transaksi peminjaman aktif untuk alat ini.</div><form class="d-none" method="post" action="{{ route($returnRoutePrefix.'.returns.store') }}" id="return-form" enctype="multipart/form-data">@csrf<input type="hidden" name="kode_alat" id="return-equipment-code"><input type="hidden" name="id_detail" id="return-detail-id"><div class="row g-3"><div class="col-12"><label class="form-label">Transaksi aktif</label><select class="form-select" name="id_peminjaman" id="loan-select" required></select></div><div class="col-12"><label class="form-label" for="return-item-unit">Unit fisik <span id="return-unit-required" class="text-danger d-none">*</span></label><select class="form-select" name="item_unit_id" id="return-item-unit"></select><div class="form-text" id="return-unit-help">Transaksi ini tidak menggunakan unit fisik; pengembalian diproses berdasarkan jumlah.</div></div><div class="col-12 col-sm-6"><label class="form-label">Jumlah yang dikembalikan</label><input class="form-control" id="return-quantity" type="number" name="jumlah" min="1" value="1" required></div><div class="col-12 col-sm-6"><label class="form-label" for="return-date">Tanggal kembali</label><input class="form-control" id="return-date" type="date" name="tanggal_kembali_aktual" value="{{ now()->toDateString() }}" readonly aria-readonly="true"><div class="form-text">Otomatis menggunakan tanggal hari ini.</div></div><div class="col-12 col-sm-6"><label class="form-label">Kondisi item</label><select class="form-select" name="kondisi_alat" required><option>Baik</option><option>Maintenance</option><option>Rusak</option><option>Hilang</option></select></div><div class="col-12 col-sm-6"><label class="form-label">Foto pengembalian/peletakan <span class="text-danger">*</span> <span class="text-muted fw-normal">(kamera belakang, maksimal 5 MB)</span></label><div id="camera-panel" class="border rounded p-2"><video id="return-camera" class="w-100 rounded d-none" autoplay playsinline></video><canvas id="return-camera-canvas" class="d-none"></canvas><img id="return-photo-preview" class="w-100 rounded d-none" alt="Pratinjau foto kondisi alat"><div class="d-grid gap-2"><button class="btn btn-outline-primary" id="open-camera" type="button"><i class="bi bi-camera me-1"></i>Ambil Foto Kondisi Alat</button><button class="btn btn-primary d-none" id="capture-photo" type="button"><i class="bi bi-camera-fill me-1"></i>Capture Foto Sekarang</button><button class="btn btn-light d-none" id="retake-photo" type="button"><i class="bi bi-arrow-repeat me-1"></i>Ambil Ulang</button></div><div id="camera-status" class="form-text">Kamera akan dibuka langsung, tanpa memilih file dari perangkat.</div></div><input class="d-none" id="return-photo" type="file" name="foto_bukti" accept="image/jpeg,image/png,image/webp" capture="environment" @required($technicianPhotoRequired)><div class="form-text">{{ $technicianPhotoRequired ? 'Wajib capture foto saat alat diterima dan diletakkan kembali.' : 'Foto bukti kondisi (opsional).' }}</div></div><div class="col-12"><label class="form-label">Catatan (opsional)</label><textarea class="form-control" name="catatan" rows="2"></textarea></div><div class="col-12 text-muted">Denda keterlambatan Rp 5.000 per hari, dibagi proporsional terhadap jumlah item transaksi.</div><div class="col-12"><button class="btn btn-primary w-100" type="submit">Simpan pengembalian item</button></div></div></form></div></div></div></div>
@endsection
@push('scripts')
<script>
const technicianReturn = @json(auth()->user()->role === 'teknisi');
let lookupInProgress = false;
let pendingManualToken = null;
const errorBox = document.getElementById('return-error');
const resultBox = document.getElementById('return-result');
const returnForm = document.getElementById('return-form');
const emptyReturnMessage = document.getElementById('no-active-return');
const returnSearchUrl = @json(route($returnRoutePrefix.'.returns.search', [], false));
let returnCameraStream = null;
const returnCamera = document.getElementById('return-camera');
const returnCanvas = document.getElementById('return-camera-canvas');
const returnPhoto = document.getElementById('return-photo');
const returnPreview = document.getElementById('return-photo-preview');
const cameraStatus = document.getElementById('camera-status');
const openCameraButton = document.getElementById('open-camera');
const capturePhotoButton = document.getElementById('capture-photo');
const retakePhotoButton = document.getElementById('retake-photo');
async function openReturnCamera() {
    if (! navigator.mediaDevices?.getUserMedia) {
        cameraStatus.textContent = 'Browser ini tidak mendukung kamera langsung. Gunakan Chrome/Edge melalui HTTPS.';
        return;
    }
    try {
        returnCameraStream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {facingMode: {ideal: 'environment'}},
        });
        returnCamera.srcObject = returnCameraStream;
        returnCamera.classList.remove('d-none');
        returnPreview.classList.add('d-none');
        openCameraButton.classList.add('d-none');
        capturePhotoButton.classList.remove('d-none');
        retakePhotoButton.classList.add('d-none');
        cameraStatus.textContent = 'Kamera belakang aktif. Pastikan alat dan lokasi peletakan terlihat.';
    } catch (error) {
        cameraStatus.textContent = 'Kamera tidak dapat dibuka. Izinkan akses kamera lalu coba lagi.';
    }
}
function stopReturnCamera() {
    returnCameraStream?.getTracks().forEach(track => track.stop());
    returnCameraStream = null;
    returnCamera.srcObject = null;
}
function captureReturnPhoto() {
    if (! returnCamera.videoWidth) return;
    returnCanvas.width = returnCamera.videoWidth;
    returnCanvas.height = returnCamera.videoHeight;
    returnCanvas.getContext('2d').drawImage(returnCamera, 0, 0);
    returnCanvas.toBlob(blob => {
        if (! blob) return;
        const capturedFile = new File([blob], `pengembalian-${Date.now()}.jpg`, {type: 'image/jpeg'});
        const transfer = new DataTransfer();
        transfer.items.add(capturedFile);
        returnPhoto.files = transfer.files;
        returnPreview.src = URL.createObjectURL(blob);
        returnPreview.classList.remove('d-none');
        returnCamera.classList.add('d-none');
        capturePhotoButton.classList.add('d-none');
        retakePhotoButton.classList.remove('d-none');
        cameraStatus.textContent = 'Foto berhasil di-capture dari kamera. Tidak ada file dari perangkat yang dipilih.';
        stopReturnCamera();
    }, 'image/jpeg', 0.9);
}
openCameraButton.addEventListener('click', openReturnCamera);
capturePhotoButton.addEventListener('click', captureReturnPhoto);
retakePhotoButton.addEventListener('click', openReturnCamera);
document.getElementById('returnModal').addEventListener('hidden.bs.modal', stopReturnCamera);
returnForm.addEventListener('submit', event => {
    if (technicianReturn && ! returnPhoto.files.length) {
        event.preventDefault();
        cameraStatus.textContent = 'Wajib capture foto kondisi alat melalui kamera sebelum menyimpan.';
        openCameraButton.focus();
    }
});
function setReturnDateNow() {
    const now = new Date();
    const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    document.getElementById('return-date').value = localNow.toISOString().slice(0, 16);
}

async function findReturn(token) {
    if (lookupInProgress) return;
    lookupInProgress = true;
    errorBox.textContent = '';
    resultBox.classList.add('d-none');
    returnForm.classList.add('d-none');
    emptyReturnMessage.classList.add('d-none');
    try {
        const response = await fetch(`${returnSearchUrl}?q=${encodeURIComponent(token)}`, {
            credentials: 'same-origin',
            headers: {Accept: 'application/json'},
        });
        if (response.status === 401) throw new Error('Sesi login berakhir. Silakan masuk kembali.');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Alat tidak ditemukan. Periksa kode alat.');
        document.getElementById('return-equipment-code').value = data.kode_alat;
        document.getElementById('equipment-result').textContent =
            `Alat: ${data.alat} · Status: ${data.status} · Stok tersedia: ${data.stok_tersedia}/${data.stok_total}`;
        resultBox.classList.remove('d-none');
        if (data.peminjaman.length === 0) {
            emptyReturnMessage.classList.remove('d-none');
            return;
        }
        const loanSelect = document.getElementById('loan-select');
        loanSelect.replaceChildren(...data.peminjaman.map(loan => {
            const option = new Option(
                `${loan.kode} · ${loan.peminjam} · sisa ${loan.sisa} · kembali ${loan.jatuh_tempo}`,
                loan.id,
            );
            option.dataset.detailId = loan.id_detail;
            option.dataset.remaining = loan.sisa;
            option.dataset.units = JSON.stringify(loan.units || []);
            return option;
        }));
        loanSelect.dispatchEvent(new Event('change'));
        returnForm.classList.remove('d-none');
    } catch (error) {
        errorBox.textContent = error.message;
    } finally {
        lookupInProgress = false;
    }
}

document.getElementById('loan-select').addEventListener('change', event => {
    const option = event.target.selectedOptions[0];
    document.getElementById('return-detail-id').value = option?.dataset.detailId || '';
    const quantity = document.getElementById('return-quantity');
    quantity.max = option?.dataset.remaining || 1;
    quantity.value = 1;
    const unitSelect = document.getElementById('return-item-unit');
    const units = JSON.parse(option?.dataset.units || '[]');
    unitSelect.replaceChildren(new Option(
        units.length ? 'Pilih Asset ID yang dikembalikan' : 'Tidak ada unit fisik untuk transaksi ini',
        '',
    ), ...units.map(unit => new Option(unit.asset_id, unit.id)));
    const usesPhysicalUnits = technicianReturn && units.length > 0;
    unitSelect.required = usesPhysicalUnits;
    unitSelect.disabled = !usesPhysicalUnits;
    document.getElementById('return-unit-required').classList.toggle('d-none', !usesPhysicalUnits);
    document.getElementById('return-unit-help').textContent = usesPhysicalUnits
        ? 'Pilih Asset ID yang benar-benar dikembalikan.'
        : 'Transaksi ini tidak menggunakan unit fisik; pengembalian diproses berdasarkan jumlah.';
    if (technicianReturn) {
        quantity.max = 1;
        quantity.value = 1;
    }
});
document.getElementById('manual-lookup').addEventListener('submit', event => {
    event.preventDefault();
    const term = document.getElementById('manual-token').value.trim();
    if (!term) return;
    pendingManualToken = term;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('returnModal')).show();
});
let searchTimer = null;
document.getElementById('manual-token').addEventListener('input', event => {
    clearTimeout(searchTimer);
    const term = event.target.value.trim();
    if (term.length < 2) return;
    searchTimer = setTimeout(() => {
        pendingManualToken = term;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('returnModal')).show();
    }, 350);
});
document.getElementById('returnModal').addEventListener('shown.bs.modal', () => {
    setReturnDateNow();
    if (pendingManualToken === null) return;
    const token = pendingManualToken;
    pendingManualToken = null;
    findReturn(token);
});
</script>
@endpush
