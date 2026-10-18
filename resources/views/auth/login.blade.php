@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
@php($selectedRole = old('role', 'siswa'))
<div class="auth-frame">
    <aside class="auth-aside">
        <a class="brand" href="{{ route('login') }}"><span class="brand-mark"><i class="bi bi-box-seam"></i></span><span>SIPAR<small>Peminjaman Alat RPL</small></span></a>
        <div class="aside-copy"><small>SMK NEGERI 5 SURAKARTA · REKAYASA PERANGKAT LUNAK</small><h1>Alat praktik, lebih mudah dikelola.</h1><p>Ajukan peminjaman, pantau verifikasi, dan kembalikan alat dalam satu sistem yang tertata.</p></div>
        <div class="aside-foot">Sistem Informasi Peminjaman Alat RPL · 2026</div>
    </aside>
    <main class="auth-main"><form class="auth-form" method="post" action="{{ route('login.store') }}">
        @csrf<h2>Masuk ke akun</h2><p>Pilih peran dan gunakan kredensial akun SIPAR Anda.</p>
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        <div class="role-switch"><button class="{{ $selectedRole === 'siswa' ? 'active' : '' }}" type="button" data-role="siswa" aria-pressed="{{ $selectedRole === 'siswa' ? 'true' : 'false' }}"><i class="bi bi-person me-1"></i>Siswa</button><button class="{{ $selectedRole === 'admin' ? 'active' : '' }}" type="button" data-role="admin" aria-pressed="{{ $selectedRole === 'admin' ? 'true' : 'false' }}"><i class="bi bi-shield-lock me-1"></i>Admin / Toolman</button></div><input type="hidden" name="role" id="login-role" value="{{ $selectedRole }}">
        <label class="form-label" for="identity" id="identity-label">{{ $selectedRole === 'admin' ? 'Username' : 'NIS' }}</label><input class="form-control mb-3" id="identity" name="identity" value="{{ old('identity') }}" placeholder="{{ $selectedRole === 'admin' ? 'Masukkan username admin' : 'Masukkan NIS atau email' }}" autocomplete="username" required autofocus>
        <label class="form-label" for="password">Password</label><input class="form-control mb-2" id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
        <div class="login-options d-flex justify-content-between align-items-center mb-4"><label class="form-check-label text-muted"><input class="form-check-input me-1" name="remember" type="checkbox" value="1"> Ingat saya</label><span class="text-muted" id="login-support">{{ $selectedRole === 'admin' ? 'Kendala akses? Hubungi pengelola SIPAR.' : 'Lupa password? Hubungi wali kelas atau admin SIPAR.' }}</span></div>
        <button class="btn btn-primary w-100" type="submit">Masuk ke SIPAR <i class="bi bi-arrow-right ms-1"></i></button>
        <div class="help-note mt-3 {{ $selectedRole === 'admin' ? 'd-none' : '' }}" id="student-registration">Akun baru? <a href="{{ route('register') }}">Daftar sebagai siswa</a></div>
    </form></main>
</div>
<script>
const roleInput=document.getElementById('login-role');
const identityLabel=document.getElementById('identity-label');
const identityInput=document.getElementById('identity');
const supportText=document.getElementById('login-support');
const studentRegistration=document.getElementById('student-registration');
function selectLoginRole(role){
    const isAdmin=role==='admin';
    roleInput.value=role;
    identityLabel.textContent=isAdmin?'Username':'NIS';
    identityInput.placeholder=isAdmin?'Masukkan username admin':'Masukkan NIS atau email';
    supportText.textContent=isAdmin?'Kendala akses? Hubungi pengelola SIPAR.':'Lupa password? Hubungi wali kelas atau admin SIPAR.';
    studentRegistration.classList.toggle('d-none',isAdmin);
    document.querySelectorAll('.role-switch button').forEach(button=>{
        const selected=button.dataset.role===role;
        button.classList.toggle('active',selected);
        button.setAttribute('aria-pressed',String(selected));
    });
}
document.querySelectorAll('.role-switch button').forEach(button=>button.addEventListener('click',()=>selectLoginRole(button.dataset.role)));
selectLoginRole(roleInput.value);
</script>
@endsection
