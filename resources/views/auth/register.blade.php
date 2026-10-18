@extends('layouts.auth')
@section('title', 'Daftar Siswa')
@section('content')
<div class="auth-frame">
    <aside class="auth-aside"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><i class="bi bi-box-seam"></i></span><span>SIPAR<small>Peminjaman Alat RPL</small></span></a><div class="aside-copy"><small>AKUN SISWA</small><h1>Mulai dari satu akun.</h1><p>Gunakan identitas sekolah untuk mengajukan peminjaman alat praktik RPL.</p></div><div class="aside-foot">SMK Negeri 5 Surakarta</div></aside>
    <main class="auth-main"><form class="auth-form" method="post" action="{{ route('register.store') }}">
        @csrf<h2>Daftar akun siswa</h2><p>Lengkapi data sekolah untuk melanjutkan.</p>
        @if($errors->any())<div class="alert alert-danger py-2">Periksa kembali data yang dimasukkan.</div>@endif
        <label class="form-label">Nama lengkap</label><input class="form-control mb-3" name="nama" value="{{ old('nama') }}" required maxlength="120">
        <label class="form-label">NIS</label><input class="form-control mb-3" name="nis_nip" value="{{ old('nis_nip') }}" required maxlength="30">
        <label class="form-label">Email</label><input class="form-control mb-3" name="email" type="email" value="{{ old('email') }}" required>
        <label class="form-label">Password</label><input class="form-control mb-3" name="password" type="password" required minlength="8" autocomplete="new-password">
        <label class="form-label">Ulangi password</label><input class="form-control mb-3" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
        <button class="btn btn-primary w-100" type="submit">Buat akun siswa</button><p class="text-center mt-3 mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </form></main>
</div>
@endsection
