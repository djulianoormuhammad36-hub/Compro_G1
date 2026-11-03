<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

/**
 * Halaman "Buat akun admin" (/daftar).
 *
 *  - Belum ada admin sama sekali  -> terbuka untuk siapa pun (akun pertama).
 *  - Sudah ada admin              -> terbuka HANYA bila ADMIN_SETUP_KEY diisi di .env,
 *                                    dan pendaftar wajib memasukkan kode itu.
 *  - Selain itu                   -> ditutup (admin baru dibuat lewat menu Akun Admin).
 *
 * Pendaftaran tanpa kode rahasia sengaja tidak ada: akun admin bisa melihat KTP penghuni.
 */
class SetupController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Setup', $this->status() + ['lokal' => app()->environment('local')]);
    }

    public function store(Request $request)
    {
        abort_unless($this->status()['terbuka'], 403, 'Pendaftaran akun admin sedang ditutup.');

        $kunci = config('app.admin.setup_key');

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'kunci'    => [filled($kunci) ? 'required' : 'nullable', 'string'],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'kunci.required'     => 'Kode pendaftaran wajib diisi.',
        ]);

        if (filled($kunci) && ! hash_equals((string) $kunci, (string) ($data['kunci'] ?? ''))) {
            return back()->withErrors(['kunci' => 'Kode pendaftaran salah (lihat ADMIN_SETUP_KEY di file .env).']);
        }

        $admin = DB::transaction(function () use ($data, $kunci) {
            // Tanpa kode, pendaftaran hanya untuk akun pertama: tolak bila keduluan permintaan lain.
            if (blank($kunci) && $this->adaAdmin()) {
                return null;
            }

            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->is_admin = true;
            $user->email_verified_at = now();
            $user->save();

            return $user;
        });

        abort_if($admin === null, 403, 'Pendaftaran akun admin sedang ditutup.');

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Akun admin dibuat. Selamat datang, ' . $admin->name . '!');
    }

    /** @return array{terbuka: bool, pertama: bool, butuhKunci: bool} */
    private function status(): array
    {
        $ada = $this->adaAdmin();
        $kunci = filled(config('app.admin.setup_key'));

        return ['terbuka' => ! $ada || $kunci, 'pertama' => ! $ada, 'butuhKunci' => $kunci];
    }

    private function adaAdmin(): bool
    {
        return User::where('is_admin', true)->exists();
    }
}