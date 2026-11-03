<?php

namespace App\Http\Middleware;

use App\Services\BookingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cadangan bila cron penjadwal belum dipasang: periksa booking kedaluwarsa
 * paling sering sekali per 30 detik saat ada request web.
 */
class HanguskanBookingKedaluwarsa
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() && Cache::add('booking:cek-kedaluwarsa', 1, 30)) {
            rescue(fn () => app(BookingService::class)->hanguskanKedaluwarsa(), report: true);
        }

        return $next($request);
    }
}
