@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
@php($selectedRole = old('role', 'siswa'))
<div class="auth-frame">
    <aside class="auth-aside">
        <a class="brand" href="{{ route('login') }}"><img class="brand-logo" src="{{ asset('assets/logo.png') }}" alt="Logo SIPAR"><span>SIPAR<small>Peminjaman Alat RPL</small></span></a>
        <div class="aside-copy"><small>SMK NEGERI 5 SURAKARTA · REKAYASA PERANGKAT LUNAK</small><h1>Alat praktik, lebih mudah dikelola.</h1><p>Ajukan peminjaman, pantau verifikasi, dan kembalikan alat dalam satu sistem yang tertata.</p></div>
        <div class="aside-foot">Sistem Informasi Peminjaman Alat RPL · 2026</div>
    </aside>
    <main class="auth-main"><form class="auth-form" method="post" action="{{ route('login.store') }}">
        @csrf<h2>Masuk ke akun</h2><p>Pilih peran dan gunakan kredensial akun SIPAR Anda.</p>
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        <div class="role-switch"><button class="{{ $selectedRole === 'siswa' ? 'active' : '' }}" type="button" data-role="siswa" aria-pressed="{{ $selectedRole === 'siswa' ? 'true' : 'false' }}"><i class="bi bi-person me-1"></i>Siswa</button><button class="{{ $selectedRole === 'admin' ? 'active' : '' }}" type="button" data-role="admin" aria-pressed="{{ $selectedRole === 'admin' ? 'true' : 'false' }}"><i class="bi bi-shield-lock me-1"></i>Admin / Toolman</button></div><input type="hidden" name="role" id="login-role" value="{{ $selectedRole }}">
        <label class="form-label" for="identity" id="identity-label">{{ $selectedRole === 'admin' ? 'Username' : 'NIS' }}</label><input class="form-control mb-3" id="identity" name="identity" value="{{ old('identity') }}" placeholder="{{ $selectedRole === 'admin' ? 'Masukkan username admin' : 'Masukkan NIS atau email' }}" autocomplete="username" required autofocus>
        <label class="form-label" for="password">Password</label><div class="password-field mb-2"><input class="form-control pe-5" id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
        <div class="login-options d-flex justify-content-between align-items-center mb-4"><label class="form-check-label text-muted login-remember"><input class="form-check-input" name="remember" type="checkbox" value="1">Ingat saya</label><span class="text-muted whitespace-nowrap" id="login-support">{{ $selectedRole === 'admin' ? '' : 'Lupa password? Hubungi Admin SIPAR.' }}</span></div>
        <button class="btn btn-primary w-100" type="submit">Masuk ke SIPAR <i class="bi bi-arrow-right ms-1"></i></button>
        <div class="student-registration-slot"><div class="help-note" id="student-registration" @if($selectedRole === 'admin') hidden @endif>Akun baru? <a href="{{ route('register') }}">Daftar sebagai siswa</a></div></div>
    </form></main>
</div>
<script>
const roleInput=document.getElementById('login-role');
const identityLabel=document.getElementById('identity-label');
const identityInput=document.getElementById('identity');
const supportText=document.getElementById('login-support');
const studentRegistration=document.getElementById('student-registration');
const passwordInput=document.getElementById('password');
const passwordToggle=document.querySelector('[data-password-toggle]');
function selectLoginRole(role){
    const isAdmin=role==='admin';
    roleInput.value=role;
    identityLabel.textContent=isAdmin?'Username':'NIS';
    identityInput.placeholder=isAdmin?'Masukkan username admin':'Masukkan NIS atau email';
    supportText.textContent=isAdmin?'':'Lupa password? Hubungi Admin SIPAR.';
    studentRegistration.hidden=isAdmin;
    document.querySelectorAll('.role-switch button').forEach(button=>{
        const selected=button.dataset.role===role;
        button.classList.toggle('active',selected);
        button.setAttribute('aria-pressed',String(selected));
    });
}
document.querySelectorAll('.role-switch button').forEach(button=>button.addEventListener('click',()=>selectLoginRole(button.dataset.role)));
selectLoginRole(roleInput.value);
passwordToggle.addEventListener('click',()=>{
    const showPassword=passwordInput.type==='password';
    passwordInput.type=showPassword?'text':'password';
    passwordToggle.setAttribute('aria-label',showPassword?'Sembunyikan password':'Tampilkan password');
    passwordToggle.setAttribute('aria-pressed',String(showPassword));
    passwordToggle.querySelector('i').classList.toggle('bi-eye',!showPassword);
    passwordToggle.querySelector('i').classList.toggle('bi-eye-slash',showPassword);
});
</script>
@endsection
