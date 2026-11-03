<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tampil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AkunAdminController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Akun/Index', [
            'akun' => User::where('is_admin', true)->orderBy('id')->get()->map(fn (User $u) => [
                'id'     => $u->id,
                'nama'   => $u->name,
                'email'  => $u->email,
                'dibuat' => Tampil::tanggal($u->created_at),
                'saya'   => $u->id === $request->user()->id,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->pesan());

        $admin = new User();
        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->password = Hash::make($data['password']);
        $admin->is_admin = true;
        $admin->email_verified_at = now();
        $admin->save();

        return back()->with('success', 'Akun admin ' . $admin->email . ' dibuat. Berikan email & password tersebut kepada yang bersangkutan.');
    }

    /** Ganti password akun sendiri (wajib mengisi password lama). */
    public function ubahPassword(Request $request)
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password'      => ['required', 'string', 'min:8', 'confirmed', 'different:password_lama'],
        ], $this->pesan() + [
            'password_lama.required'         => 'Password lama wajib diisi.',
            'password_lama.current_password' => 'Password lama salah.',
            'password.different'             => 'Password baru harus berbeda dari password lama.',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password Anda berhasil diganti.');
    }

    /** Atur ulang password admin lain (mis. lupa password). */
    public function resetPassword(Request $request, $id)
    {
        $admin = User::where('is_admin', true)->findOrFail($id);

        abort_if($admin->id === $request->user()->id, 422, 'Gunakan "Ganti password saya" untuk akun sendiri.');

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->pesan());

        $admin->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password ' . $admin->email . ' diatur ulang.');
    }

    public function destroy(Request $request, $id)
    {
        $admin = User::where('is_admin', true)->findOrFail($id);

        if ($admin->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        }

        if (User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun admin.');
        }

        $admin->delete();

        return back()->with('success', 'Akun admin ' . $admin->email . ' dihapus.');
    }

    private function pesan(): array
    {
        return [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }
}