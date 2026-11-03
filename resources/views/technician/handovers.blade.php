@extends('layouts.app')
@section('title', 'Penyerahan Alat')
@section('content')
<div class="page-head"><div><h1>Penyerahan Alat</h1><p>Serahkan alat kepada siswa dan catat kondisi fisiknya.</p></div></div>
<section class="surface">
    <div class="table-responsive">
        <table class="table"><thead><tr><th>Transaksi</th><th>Siswa</th><th>Alat</th><th>Jadwal kembali</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($loans as $loan)
            @foreach($loan->detail as $detail)
                @php($handed = $detail->penyerahanDetail->sum('jumlah'))
                @if($handed < $detail->jumlah)
                    <tr><td>PJM-{{ str_pad($loan->id_peminjaman, 5, '0', STR_PAD_LEFT) }}</td><td>{{ $loan->user->nama }}<small class="d-block text-muted">{{ $loan->user->nis_nip }}</small></td><td>{{ $detail->alat->nama_alat }}<small class="d-block text-muted">{{ $handed }}/{{ $detail->jumlah }} unit diserahkan</small></td><td>{{ $loan->tanggal_kembali_rencana->format('d M Y') }}</td><td><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#handover-{{ $detail->id_detail }}">Catat penyerahan</button></td></tr>
                @endif
            @endforeach
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi yang perlu diserahkan.</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div class="p-3">{{ $loans->links() }}</div>
</section>
@foreach($loans as $loan)
    @foreach($loan->detail as $detail)
        @php($handed = $detail->penyerahanDetail->sum('jumlah'))
        @if($handed < $detail->jumlah)
        <div class="modal fade" id="handover-{{ $detail->id_detail }}" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('technician.handovers.store', $loan) }}">@csrf
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold">Catat Penyerahan Alat</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><input type="hidden" name="id_detail" value="{{ $detail->id_detail }}"><p class="mb-3"><strong>{{ $detail->alat->nama_alat }}</strong><br><span class="text-muted">{{ $loan->user->nama }} · Sisa {{ $detail->jumlah - $handed }} unit</span></p><label class="form-label">Jumlah diserahkan</label><input class="form-control mb-3" type="number" name="jumlah" min="1" max="{{ $detail->jumlah - $handed }}" value="{{ $detail->jumlah - $handed }}" required><label class="form-label">Kondisi saat diserahkan</label><select class="form-select mb-3" name="kondisi_alat" required><option value="Baik">Baik</option><option value="Rusak">Rusak</option></select><label class="form-label">Catatan</label><textarea class="form-control" name="catatan" rows="2"></textarea></div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan penyerahan</button></div>
        </form></div></div>
        @endif
    @endforeach
@endforeach
@endsection
