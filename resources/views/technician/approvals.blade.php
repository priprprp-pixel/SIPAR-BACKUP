@extends('layouts.app')
@section('title', 'Verifikasi Teknisi')
@section('content')
<div class="page-head"><div><h1>Verifikasi Peminjaman</h1><p>Pilih unit fisik yang benar-benar tersedia dan berkondisi baik.</p></div></div>
<section class="surface">
@forelse($loans as $loan)
<form class="border-bottom p-3 p-md-4" method="post" action="{{ route('technician.approvals.approve', $loan) }}">
    @csrf
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><strong>PJM-{{ str_pad($loan->id_peminjaman, 5, '0', STR_PAD_LEFT) }} · {{ $loan->user->nama }}</strong><small class="d-block text-muted">{{ $loan->user->nis_nip }} · {{ $loan->tanggal_kembali_rencana->format('d M Y') }}</small></div><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>Setujui & kunci unit</button></div>
    @foreach($loan->detail as $detail)
        @php($units = $detail->alat->itemUnits()->where('condition', 'Baik')->where('status', 'Tersedia')->orderBy('asset_id')->get())
        <div class="surface p-3 mb-2"><div class="d-flex justify-content-between gap-2 mb-2"><div class="d-flex align-items-center gap-2"><span class="equipment-icon overflow-hidden"><x-equipment-image :equipment="$detail->alat" alt="Foto {{ $detail->alat->nama_alat }}"></x-equipment-image></span><div><strong>{{ $detail->alat->kode_alat }} · {{ $detail->alat->nama_alat }}</strong><small class="d-block text-muted">Pilih tepat {{ $detail->jumlah }} unit</small></div></div><span class="badge-status status-dipinjam">{{ $units->count() }} tersedia</span></div>
            <div class="row g-2">
            @forelse($units as $unit)
                <div class="col-12 col-sm-6 col-lg-4"><label class="form-check border rounded p-2 d-flex gap-2 align-items-center"><input class="form-check-input m-0 unit-choice" type="checkbox" name="units[{{ $detail->id_detail }}][]" value="{{ $unit->item_unit_id }}" data-detail="{{ $detail->id_detail }}" data-limit="{{ $detail->jumlah }}"><span><strong>{{ $unit->asset_id }}</strong></span></label></div>
            @empty
                <div class="col-12"><div class="alert alert-warning mb-0">Tidak ada unit Baik yang tersedia untuk alat ini.</div></div>
            @endforelse
            </div>
        </div>
    @endforeach
</form>
@empty
<div class="p-5 text-center text-muted">Belum ada pengajuan Pending.</div>
@endforelse
</section>
<div class="mt-3">{{ $loans->links() }}</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.unit-choice').forEach(input => input.addEventListener('change', event => {
    const current = document.querySelectorAll(`.unit-choice[data-detail="${event.target.dataset.detail}"]:checked`);
    if (current.length > Number(event.target.dataset.limit)) event.target.checked = false;
}));
</script>
@endpush
