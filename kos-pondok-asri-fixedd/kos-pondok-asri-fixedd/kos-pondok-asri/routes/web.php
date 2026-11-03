<?php

use App\Http\Controllers\Admin\AkunAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FasilitasController;
use App\Http\Controllers\Admin\InfoKosController;
use App\Http\Controllers\Admin\KamarController;
use App\Http\Controllers\Admin\PenghuniController;
use App\Http\Controllers\Admin\KonfirmasiPembayaranController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingProcessController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Aplikasi Pondok Asri
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC ROUTES
// ==========================================

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/tentang', [PublicController::class, 'tentang'])->name('tentang');
Route::get('/kamar', [PublicController::class, 'katalogKamar'])->name('kamar.index');
Route::get('/kamar/{kamar}', [PublicController::class, 'detailKamar'])->name('kamar.show');

// Alur booking: form (di halaman detail kamar) -> review -> pembayaran -> bukti
Route::post('/kamar/{kamar}/booking', [BookingProcessController::class, 'storeBooking'])->middleware('throttle:10,1')->name('booking.store');
Route::get('/booking/{booking}', [BookingProcessController::class, 'reviewBooking'])->name('booking.review');
Route::get('/booking/{booking}/pembayaran', [BookingProcessController::class, 'paymentMethod'])->name('booking.payment');
Route::post('/booking/{booking}/pembayaran', [BookingProcessController::class, 'processPembayaran'])->middleware('throttle:10,1')->name('booking.pay');
Route::get('/pembayaran/{pembayaran}/bukti', [BookingProcessController::class, 'buktiPembayaran'])->name('booking.bukti');

// ==========================================
// 2. AUTH ADMIN
// ==========================================

// Buat akun admin lewat website: terbuka untuk akun pertama, atau dengan kode ADMIN_SETUP_KEY dari .env
Route::get('/daftar', [SetupController::class, 'show'])->name('daftar');
Route::post('/daftar', [SetupController::class, 'store'])->middleware('throttle:5,1')->name('daftar.store');
Route::redirect('/setup', '/daftar'); // alamat lama

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ==========================================
// 3. ADMIN ROUTES (wajib login sebagai admin)
// ==========================================

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    Route::redirect('/', '/admin/dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'exportReport'])->name('dashboard.export');

    // Kamar (tambah/ubah lewat modal; foto kamar & status bisa diubah)
    Route::resource('kamar', KamarController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/kamar/{id}/status', [KamarController::class, 'updateStatus'])->name('kamar.status');

    // Fasilitas
    Route::resource('fasilitas', FasilitasController::class)->only(['index', 'store', 'update', 'destroy']);

    // Penghuni (lihat identitas, tandai keluar, hapus)
    Route::get('/penghuni', [PenghuniController::class, 'index'])->name('penghuni.index');
    Route::get('/penghuni/{id}', [PenghuniController::class, 'show'])->name('penghuni.show');
    Route::post('/penghuni/{id}/keluar', [PenghuniController::class, 'keluar'])->name('penghuni.keluar');
    Route::delete('/penghuni/{id}', [PenghuniController::class, 'destroy'])->name('penghuni.destroy');

    // Konfirmasi pembayaran
    Route::get('/konfirmasi', [KonfirmasiPembayaranController::class, 'index'])->name('konfirmasi.index');
    Route::get('/konfirmasi/{id_pembayaran}', [KonfirmasiPembayaranController::class, 'show'])->name('konfirmasi.show');
    Route::get('/konfirmasi/{id_pembayaran}/bukti', [KonfirmasiPembayaranController::class, 'bukti'])->name('konfirmasi.bukti');
    Route::post('/konfirmasi/{id_pembayaran}/approve', [KonfirmasiPembayaranController::class, 'approve'])->name('konfirmasi.approve');
    Route::post('/konfirmasi/{id_pembayaran}/reject', [KonfirmasiPembayaranController::class, 'reject'])->name('konfirmasi.reject');

    // Akun admin (tambah, hapus, ganti/atur ulang password)
    Route::get('/akun', [AkunAdminController::class, 'index'])->name('akun.index');
    Route::post('/akun', [AkunAdminController::class, 'store'])->name('akun.store');
    Route::put('/akun/password', [AkunAdminController::class, 'ubahPassword'])->name('akun.password');
    Route::put('/akun/{id}/reset', [AkunAdminController::class, 'resetPassword'])->name('akun.reset');
    Route::delete('/akun/{id}', [AkunAdminController::class, 'destroy'])->name('akun.destroy');

    // Info kos (nama, deskripsi, kontak, lokasi, peraturan, rekening/QRIS, foto)
    Route::get('/info-kos', [InfoKosController::class, 'edit'])->name('info-kos.edit');
    Route::put('/info-kos', [InfoKosController::class, 'update'])->name('info-kos.update');
});