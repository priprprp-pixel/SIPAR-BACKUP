<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::in(['admin', 'siswa'])],
        ]);

        $column = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'nis_nip';
        $remember = $request->boolean('remember');

        if (! Auth::attempt([$column => $credentials['identity'], 'password' => $credentials['password']], $remember)) {
            return back()->withErrors(['identity' => 'NIS/NIP atau password tidak cocok.'])->onlyInput('identity');
        }

        if (Auth::user()->role !== $credentials['role']) {
            Auth::logout();

            return back()->withErrors(['identity' => 'Akun ini tidak memiliki akses untuk peran yang dipilih.'])->onlyInput('identity');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'nis_nip' => ['required', 'string', 'max:30', 'unique:users,nis_nip'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $data['role'] = 'siswa';

        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Akun siswa berhasil dibuat.');
    }

    public function profile(): View
    {
        return view('profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id_user.',id_user'],
        ]);
        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
