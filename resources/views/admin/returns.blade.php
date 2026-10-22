@extends('layouts.app')
@section('title','Pengembalian Alat')
@section('content')
<div class="page-head"><div><h1>Proses Pengembalian</h1><p>{{ config('app.enable_qr_scanner') ? 'Pindai QR alat untuk menemukan transaksi aktif dan mencatat kondisi kembali.' : 'Masukkan kode alat untuk menemukan transaksi aktif dan mencatat kondisi kembali.' }}</p></div>@if(config('app.enable_qr_scanner'))<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="bi bi-qr-code-scan me-1"></i>Scan QR alat</button>@endif</div>
<section class="surface p-3 p-md-4 mb-3"><div class="d-flex justify-content-between align-items-center"><div><h2 class="panel-title">Peminjaman Aktif</h2><div class="panel-subtitle">{{ config('app.enable_qr_scanner') ? 'Pilih transaksi dan masukkan token QR alat fisik.' : 'Cari alat menggunakan kode alat yang tertera pada inventaris.' }}</div></div><span class="badge-status status-dipinjam">{{ $activeLoans->total() }} transaksi</span></div><form class="row g-2 mt-3" id="manual-lookup"><div class="col"><input class="form-control" id="manual-token" placeholder="{{ config('app.enable_qr_scanner') ? 'Token QR alat' : 'Kode alat, contoh: LAN-001' }}" required></div><div class="col-auto"><button class="btn btn-light" type="submit"><i class="bi bi-search me-1"></i>Cari</button></div></form>@if(config('app.enable_qr_scanner'))<div class="text-muted mt-2" style="font-size:10px">Scan kamera membutuhkan izin browser; jalankan melalui HTTPS atau localhost.</div>@endif</section>
<section class="surface"><div class="table-responsive"><table class="table"><thead><tr><th>Transaksi</th><th>Peminjam</th><th>Alat</th><th>Rencana Kembali</th><th>Status</th></tr></thead><tbody>@forelse($activeLoans as $loan)<tr><td>PJM-{{ str_pad($loan->id_peminjaman,5,'0',STR_PAD_LEFT) }}</td><td>{{ $loan->user->nama }}<small class="d-block text-muted">{{ $loan->user->nis_nip }}</small></td><td>{{ $loan->detail->map(fn($d)=>$d->alat->nama_alat.' · sisa '.max(0,$d->jumlah-$d->pengembalianDetail->sum('jumlah')).'/'.$d->jumlah)->join(', ') }}</td><td>{{ $loan->tanggal_kembali_rencana->format('d M Y') }}</td><td><span class="badge-status status-approved">Dipinjam</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi aktif.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $activeLoans->links() }}</div></section>
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold">{{ config('app.enable_qr_scanner') ? 'Scan pengembalian' : 'Pengembalian berdasarkan kode alat' }}</h2><div class="text-muted" style="font-size:11px">{{ config('app.enable_qr_scanner') ? 'Arahkan kamera ke QR code alat.' : 'Masukkan kode alat pada kolom pencarian manual.' }}</div></div><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">@if(config('app.enable_qr_scanner'))<div id="return-reader" class="qr-box"></div>@endif<div id="return-error" class="text-danger text-center mt-2" style="font-size:12px"></div><div id="return-result" class="d-none mt-3"><div class="alert alert-info py-2 mb-2" id="scanned-equipment"></div><div id="no-active-return" class="alert alert-warning d-none mb-0">{{ config('app.enable_qr_scanner') ? 'QR alat dikenali' : 'Kode alat dikenali' }}, tetapi tidak ada sisa transaksi peminjaman aktif untuk alat ini. Pengembalian hanya dapat dicatat untuk alat yang sedang dipinjam.</div><form class="d-none" method="post" action="{{ route('admin.returns.store') }}" id="return-form">@csrf<input type="hidden" name="{{ config('app.enable_qr_scanner') ? 'qr_code' : 'kode_alat' }}" id="return-token"><input type="hidden" name="id_detail" id="return-detail-id"><label class="form-label">Transaksi aktif</label><select class="form-select mb-3" name="id_peminjaman" id="loan-select" required></select><label class="form-label">Jumlah yang dikembalikan</label><input class="form-control mb-3" id="return-quantity" type="number" name="jumlah" min="1" value="1" required><label class="form-label">Tanggal kembali</label><input class="form-control mb-3" type="datetime-local" name="tanggal_kembali_aktual" value="{{ now()->format('Y-m-d\\TH:i') }}"><label class="form-label">Kondisi item</label><select class="form-select mb-3" name="kondisi_alat" required><option>Baik</option><option>Rusak</option><option>Hilang</option></select><label class="form-label">Catatan (opsional)</label><textarea class="form-control mb-3" name="catatan" rows="2"></textarea><div class="text-muted mb-3">Denda keterlambatan Rp 5.000 per hari, dibagi proporsional terhadap jumlah item transaksi.</div><button class="btn btn-primary w-100" type="submit">Simpan pengembalian item</button></form></div></div></div></div>
@endsection
@push('scripts')
@if(config('app.enable_qr_scanner'))<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>@endif
<script>
let returnScanner;
let lookupInProgress = false;
const errorBox = document.getElementById('return-error');
const resultBox = document.getElementById('return-result');
const returnForm = document.getElementById('return-form');
const emptyReturnMessage = document.getElementById('no-active-return');
const returnLookupUrl = @json(route('admin.returns.lookup', ['token' => '__TOKEN__'], false));

async function findReturn(token) {
    if (lookupInProgress) return;
    lookupInProgress = true;
    errorBox.textContent = '';
    resultBox.classList.add('d-none');
    returnForm.classList.add('d-none');
    emptyReturnMessage.classList.add('d-none');

    try {
        const response = await fetch(returnLookupUrl.replace('__TOKEN__', encodeURIComponent(token)), {
            credentials: 'same-origin',
            headers: {Accept: 'application/json'},
        });
        if (response.status === 401) throw new Error('Sesi login berakhir. Silakan masuk kembali, lalu pindai QR ulang.');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'QR tidak dikenali.');

        document.getElementById('return-token').value = @json(config('app.enable_qr_scanner')) ? data.qr_code : data.kode_alat;
        document.getElementById('scanned-equipment').textContent =
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
            return option;
        }));
        loanSelect.dispatchEvent(new Event('change'));
        returnForm.classList.remove('d-none');
    } catch (error) {
        errorBox.textContent = error.message;
    } finally {
        lookupInProgress = false;
        if (returnScanner?.isScanning) await returnScanner.stop();
    }
}

document.getElementById('loan-select').addEventListener('change', event => {
    const option = event.target.selectedOptions[0];
    document.getElementById('return-detail-id').value = option?.dataset.detailId || '';
    const quantity = document.getElementById('return-quantity');
    quantity.max = option?.dataset.remaining || 1;
    quantity.value = 1;
});
document.getElementById('manual-lookup').addEventListener('submit', event => {
    event.preventDefault();
    @if(!config('app.enable_qr_scanner'))bootstrap.Modal.getOrCreateInstance(document.getElementById('returnModal')).show();@endif
    findReturn(document.getElementById('manual-token').value.trim());
});
@if(config('app.enable_qr_scanner'))
document.getElementById('returnModal').addEventListener('shown.bs.modal', async () => {
    if (!window.Html5Qrcode) {
        errorBox.textContent = 'Pemindai tidak tersedia. Gunakan token QR secara manual.';
        return;
    }
    returnScanner = new Html5Qrcode('return-reader');
    try {
        await returnScanner.start({facingMode: 'environment'}, {fps: 10, qrbox: {width: 210, height: 210}}, findReturn);
    } catch {
        errorBox.textContent = 'Izinkan akses kamera untuk memindai QR.';
    }
});
document.getElementById('returnModal').addEventListener('hidden.bs.modal', () => {
    if (returnScanner?.isScanning) returnScanner.stop();
    returnScanner = null;
});
@endif
</script>
@endpush
