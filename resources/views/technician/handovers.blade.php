@extends('layouts.app')
@section('title', 'Penyerahan Alat')
@section('content')
<div class="page-head"><div><h1>Penyerahan Alat</h1><p>Serahkan alat kepada siswa dan catat kondisi fisiknya.</p></div></div>
<section class="surface">
    <div class="table-responsive">
        <table class="table"><thead><tr><th>Transaksi</th><th>Siswa</th><th>Kode alat</th><th>Nama alat</th><th>Jadwal kembali</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($loans as $loan)
            @foreach($loan->detail as $detail)
                @php($handed = $detail->penyerahanDetail->sum('jumlah'))
                @if($handed < $detail->jumlah)
                    <tr><td>PJM-{{ str_pad($loan->id_peminjaman, 5, '0', STR_PAD_LEFT) }}</td><td>{{ $loan->user->nama }}<small class="d-block text-muted">{{ $loan->user->nis_nip }}</small></td><td><span class="badge-status status-dipinjam">{{ $detail->alat->kode_alat }}</span></td><td>{{ $detail->alat->nama_alat }}<small class="d-block text-muted">{{ $handed }}/{{ $detail->jumlah }} unit diserahkan</small></td><td>{{ $loan->tanggal_kembali_rencana->format('d M Y') }}</td><td><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#handover-{{ $detail->id_detail }}">Catat penyerahan</button></td></tr>
                @endif
            @endforeach
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi yang perlu diserahkan.</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div class="p-3">{{ $loans->links() }}</div>
</section>
@foreach($loans as $loan)
    @foreach($loan->detail as $detail)
        @php($handed = $detail->penyerahanDetail->sum('jumlah'))
        @if($handed < $detail->jumlah)
        <div class="modal fade" id="handover-{{ $detail->id_detail }}" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable operational-modal"><form class="modal-content" method="post" action="{{ route('technician.handovers.store', $loan) }}" enctype="multipart/form-data">@csrf
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold">Catat Penyerahan Alat</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            @php($assignedUnits = $detail->itemUnits->where('status', 'Dipinjam'))
            @php($handedUnitIds = $detail->penyerahanDetail->pluck('item_unit_id')->filter())
            <div class="modal-body"><input type="hidden" name="id_detail" value="{{ $detail->id_detail }}"><p class="mb-3"><strong>{{ $detail->alat->kode_alat }} · {{ $detail->alat->nama_alat }}</strong><br><span class="text-muted">{{ $loan->user->nama }} · Sisa {{ $detail->jumlah - $handed }} unit</span></p><div class="row g-3"><div class="col-12 col-sm-6"><label class="form-label">Jumlah diserahkan</label><input class="form-control handover-quantity" type="number" name="jumlah" min="1" max="{{ $detail->jumlah - $handed }}" value="{{ $detail->jumlah - $handed }}" required></div><div class="col-12 col-sm-6"><label class="form-label">Kondisi saat diserahkan</label><select class="form-select" name="kondisi_alat" required><option value="Baik">Baik</option><option value="Rusak">Rusak</option></select></div>@if($assignedUnits->isNotEmpty())<div class="col-12"><label class="form-label">Asset ID yang diserahkan</label><div class="row g-2">@foreach($assignedUnits->whereNotIn('item_unit_id', $handedUnitIds) as $unit)<div class="col-12 col-sm-6"><label class="form-check border rounded p-2"><input class="form-check-input handover-unit" type="checkbox" name="item_unit_ids[]" value="{{ $unit->item_unit_id }}"><strong class="ms-1">{{ $unit->asset_id }}</strong></label></div>@endforeach</div><div class="form-text">Pilih Asset ID sesuai jumlah yang diserahkan.</div></div>@endif<div class="col-12"><label class="form-label">Foto peletakan/penyerahan <span class="text-danger">*</span> <span class="text-muted fw-normal">(maksimal 5 MB)</span></label><input class="form-control" type="file" name="foto_bukti" accept="image/jpeg,image/png,image/webp" capture="environment" required><div class="form-text">Wajib foto alat saat diserahkan untuk bukti audit.</div></div><div class="col-12"><label class="form-label">Catatan</label><textarea class="form-control" name="catatan" rows="2"></textarea></div></div></div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan penyerahan</button></div>
        </form></div></div>
        @endif
    @endforeach
@endforeach
@endsection
@push('scripts')
<script>
document.querySelectorAll('.modal').forEach(modal => {
    const quantity = modal.querySelector('.handover-quantity');
    const units = modal.querySelectorAll('.handover-unit');
    if (!quantity || !units.length) return;
    const sync = () => {
        const selected = modal.querySelectorAll('.handover-unit:checked').length;
        quantity.value = selected || 1;
        quantity.max = units.length;
    };
    units.forEach(unit => unit.addEventListener('change', () => {
        const selected = modal.querySelectorAll('.handover-unit:checked').length;
        if (selected > Number(quantity.max)) unit.checked = false;
        sync();
    }));
    quantity.addEventListener('change', () => {
        const target = Math.max(1, Math.min(Number(quantity.value), units.length));
        units.forEach((unit, index) => { unit.checked = index < target; });
        sync();
    });
});
</script>
@endpush
