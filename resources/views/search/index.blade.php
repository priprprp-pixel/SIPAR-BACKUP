@extends('layouts.app')
@section('title', 'Pencarian')
@section('content')
<div class="page-head">
    <div>
        <h1>Hasil Pencarian</h1>
        <p>{{ $term !== '' ? 'Hasil untuk “'.$term.'”' : 'Masukkan kata kunci pada kolom pencarian.' }}</p>
    </div>
</div>

@if($term !== '')
    <div class="row g-3">
        <div class="col-xl-6">
            <section class="surface p-3 p-md-4 h-100">
                <h2 class="panel-title mb-3">Alat <span class="text-muted">({{ $equipment->count() }})</span></h2>
                @forelse($equipment as $item)
                    <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                        <div><strong>{{ $item->nama_alat }}</strong><div class="panel-subtitle">{{ $item->kode_alat }} · {{ $item->kategori->nama_kategori }}</div></div>
                        <a class="btn btn-light btn-sm align-self-center" href="{{ $isAdmin ? route('admin.equipment', ['q' => $item->kode_alat]) : route('catalog', ['q' => $item->kode_alat]) }}">Buka</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">Alat tidak ditemukan.</p>
                @endforelse
            </section>
        </div>

        @if($isAdmin)
            <div class="col-xl-6">
                <section class="surface p-3 p-md-4 h-100">
                    <h2 class="panel-title mb-3">Siswa <span class="text-muted">({{ $students->count() }})</span></h2>
                    @forelse($students as $student)
                        <div class="border-bottom py-2"><strong>{{ $student->nama }}</strong><div class="panel-subtitle">{{ $student->nis_nip }} · {{ $student->email }}</div></div>
                    @empty
                        <p class="text-muted mb-0">Siswa tidak ditemukan.</p>
                    @endforelse
                </section>
            </div>
        @endif

        <div class="col-12">
            <section class="surface p-3 p-md-4">
                <h2 class="panel-title mb-3">Transaksi <span class="text-muted">({{ $loans->count() }})</span></h2>
                @forelse($loans as $loan)
                    <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom py-2">
                        <div>
                            <strong>PJM-{{ str_pad($loan->id_peminjaman, 5, '0', STR_PAD_LEFT) }} · {{ $loan->status }}</strong>
                            <div class="panel-subtitle">{{ $loan->detail->pluck('alat.nama_alat')->filter()->join(', ') ?: $loan->keperluan }}</div>
                            @if($isAdmin)<div class="panel-subtitle">{{ $loan->user?->nama }} · {{ $loan->user?->nis_nip }}</div>@endif
                        </div>
                        <a class="btn btn-light btn-sm align-self-center" href="{{ $isAdmin ? route('admin.reports') : route('loans.mine') }}">Lihat transaksi</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">Transaksi tidak ditemukan.</p>
                @endforelse
            </section>
        </div>
    </div>
@endif
@endsection