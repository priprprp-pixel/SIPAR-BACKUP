@extends('layouts.app')
@section('title','Pengembalian Alat')
@section('content')
<div class="page-head"><div><h1>Proses Pengembalian</h1><p>Masukkan kode alat untuk menemukan transaksi aktif.</p></div></div>
@php($returnRoutePrefix = auth()->user()->role === 'teknisi' ? 'technician' : 'admin')
<section class="surface p-3 p-md-4 mb-3"><div class="d-flex justify-content-between align-items-center"><div><h2 class="panel-title">Penerimaan Kembali Alat</h2><div class="panel-subtitle">Cari alat dengan kode yang tertera pada inventaris.</div></div><span class="badge-status status-dipinjam">{{ $activeLoans->total() }} transaksi</span></div><form class="row g-2 mt-3" id="manual-lookup"><div class="col"><label class="form-label" for="manual-token">Kode alat</label><input class="form-control" id="manual-token" placeholder="Masukkan kode alat, contoh: KOM-001" required></div><div class="col-auto align-self-end"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari alat</button></div></form></section>
<section class="surface"><div class="table-responsive"><table class="table"><thead><tr><th>Transaksi</th><th>Peminjam</th><th>Alat</th><th>Rencana Kembali</th><th>Status</th></tr></thead><tbody>@forelse($activeLoans as $loan)<tr><td>PJM-{{ str_pad($loan->id_peminjaman,5,'0',STR_PAD_LEFT) }}</td><td>{{ $loan->user->nama }}<small class="d-block text-muted">{{ $loan->user->nis_nip }}</small></td><td>{{ $loan->detail->map(fn($d)=>$d->alat->nama_alat.' · sisa '.max(0,$d->jumlah-$d->pengembalianDetail->sum('jumlah')).'/'.$d->jumlah)->join(', ') }}</td><td>{{ $loan->tanggal_kembali_rencana->format('d M Y') }}</td><td><span class="badge-status status-approved">Dipinjam</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi aktif.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $activeLoans->links() }}</div></section>
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered operational-modal"><div class="modal-content"><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold">Penerimaan Kembali Alat</h2><div class="text-muted" style="font-size:11px">Periksa alat berdasarkan kode inventaris.</div></div><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div id="return-error" class="text-danger text-center mt-2" style="font-size:12px"></div><div id="return-result" class="d-none mt-3"><div class="alert alert-info py-2 mb-2" id="scanned-equipment"></div><div id="no-active-return" class="alert alert-warning d-none mb-0">Alat ditemukan, tetapi tidak ada sisa transaksi peminjaman aktif untuk alat ini.</div><form class="d-none" method="post" action="{{ route($returnRoutePrefix.'.returns.store') }}" id="return-form" enctype="multipart/form-data">@csrf<input type="hidden" name="kode_alat" id="return-equipment-code"><input type="hidden" name="id_detail" id="return-detail-id"><div class="row g-3"><div class="col-12"><label class="form-label">Transaksi aktif</label><select class="form-select" name="id_peminjaman" id="loan-select" required></select></div><div class="col-12 col-sm-6"><label class="form-label">Jumlah yang dikembalikan</label><input class="form-control" id="return-quantity" type="number" name="jumlah" min="1" value="1" required></div><div class="col-12 col-sm-6"><label class="form-label">Tanggal kembali</label><input class="form-control" type="datetime-local" name="tanggal_kembali_aktual" value="{{ now()->format('Y-m-d\\TH:i') }}"></div><div class="col-12 col-sm-6"><label class="form-label">Kondisi item</label><select class="form-select" name="kondisi_alat" required><option>Baik</option><option>Rusak</option><option>Hilang</option></select></div><div class="col-12 col-sm-6"><label class="form-label">Foto bukti kondisi <span class="text-muted fw-normal">(opsional, maksimal 5 MB)</span></label><input class="form-control" type="file" name="foto_bukti" accept="image/jpeg,image/png,image/webp" capture="environment"><div class="form-text">Ambil foto alat dari kamera HP atau pilih dari perangkat.</div></div><div class="col-12"><label class="form-label">Catatan (opsional)</label><textarea class="form-control" name="catatan" rows="2"></textarea></div><div class="col-12 text-muted">Denda keterlambatan Rp 5.000 per hari, dibagi proporsional terhadap jumlah item transaksi.</div><div class="col-12"><button class="btn btn-primary w-100" type="submit">Simpan pengembalian item</button></div></div></form></div></div></div></div>
@endsection
@push('scripts')
<script>
let lookupInProgress = false;
let pendingManualToken = null;
const errorBox = document.getElementById('return-error');
const resultBox = document.getElementById('return-result');
const returnForm = document.getElementById('return-form');
const emptyReturnMessage = document.getElementById('no-active-return');
const returnLookupUrl = @json(route($returnRoutePrefix.'.returns.lookup', ['token' => '__TOKEN__'], false));

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
        if (response.status === 401) throw new Error('Sesi login berakhir. Silakan masuk kembali.');
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Alat tidak ditemukan. Periksa kode alat.');
        document.getElementById('return-equipment-code').value = data.kode_alat;
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
    const token = document.getElementById('manual-token').value.trim();
    if (!token) return;
    pendingManualToken = token;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('returnModal')).show();
});
document.getElementById('returnModal').addEventListener('shown.bs.modal', () => {
    if (pendingManualToken === null) return;
    const token = pendingManualToken;
    pendingManualToken = null;
    findReturn(token);
});
</script>
@endpush
