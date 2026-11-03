<?php

namespace App\Http\Middleware;

use App\Models\KosInfo;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Data yang tersedia di semua halaman React.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'kos' => function () {
                $kos = KosInfo::first();

                return $kos ? [
                    'nama'    => $kos->nama_kos,
                    'kontak'  => $kos->kontak_pemilik,
                    'wa'      => $kos->whatsappLink('Halo, saya ingin bertanya tentang ' . $kos->nama_kos . '.'),
                    'foto'    => $kos->foto_url,
                ] : null;
            },
            'auth' => [
                'user' => fn () => $request->user()?->only('name', 'email'),
            ],
            // jumlah pembayaran yang menunggu konfirmasi (lencana di menu admin)
            'pending' => fn () => $request->user()?->is_admin
                ? Pembayaran::where('status_pembayaran', Pembayaran::MENUNGGU)->count()
                : 0,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
            ],
        ]);
    }
}