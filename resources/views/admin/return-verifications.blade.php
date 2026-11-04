@extends('layouts.app')
@section('title', 'Verifikasi Pengembalian')
@section('content')
<div class="page-head">
    <div><h1>Verifikasi Pengembalian</h1><p>Periksa laporan teknisi dan setujui sebelum stok diperbarui.</p></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section class="surface p-3 p-md-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Transaksi</th><th>Peminjam</th><th>Alat</th><th>Teknisi</th><th>Kondisi</th><th>Foto</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($pendingReturns as $return)
                <tr>
                    <td>PJM-{{ str_pad($return->detailPeminjaman->peminjaman->id_peminjaman, 5, '0', STR_PAD_LEFT) }}<small class="d-block text-muted">{{ $return->tanggal_kembali_aktual->format('d M Y H:i') }}</small></td>
                    <td>{{ $return->detailPeminjaman->peminjaman->user->nama }}</td>
                    <td><div class="d-flex align-items-center gap-2"><span class="equipment-icon overflow-hidden"><x-equipment-image :equipment="$return->detailPeminjaman->alat" alt="Foto {{ $return->detailPeminjaman->alat->nama_alat }}"></x-equipment-image></span><span>{{ $return->detailPeminjaman->alat->nama_alat }} × {{ $return->jumlah }} @if($return->itemUnit)<small class="d-block text-muted">{{ $return->itemUnit->asset_id }}</small>@endif</span></div></td>
                    <td>{{ $return->teknisi?->nama ?? '-' }}</td>
                    <td><span class="badge-status status-{{ strtolower($return->kondisi_alat) }}">{{ $return->kondisi_alat }}</span></td>
                    <td>@if($return->foto_bukti)<a href="{{ asset('storage/'.$return->foto_bukti) }}" target="_blank" rel="noopener">Lihat foto</a>@else<span class="text-muted">-</span>@endif</td>
                    <td><form method="post" action="{{ route('admin.returns.verify.approve', $return) }}">@csrf<button class="btn btn-primary btn-sm">Setujui</button></form></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada pengembalian yang menunggu verifikasi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $pendingReturns->links() }}
</section>
@endsection
