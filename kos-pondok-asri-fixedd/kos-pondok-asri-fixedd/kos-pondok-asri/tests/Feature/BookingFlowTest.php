<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Kamar;
use App\Models\KosInfo;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function kamar(string $nomor = '101', string $status = 'kosong'): Kamar
    {
        return Kamar::create(['nomor_kamar' => $nomor, 'biaya_sewa' => 800000, 'status_kamar' => $status]);
    }

    private function data(array $o = []): array
    {
        return array_merge([
            'nama_pendaftar'  => 'Budi Santoso',
            'no_ktp'          => '3303010101010001',
            'kontak'          => '081234567890',
            'tanggal_booking' => now()->addDays(3)->toDateString(),
        ], $o);
    }

    private function booking(Kamar $kamar, array $o = []): Booking
    {
        $this->post(route('booking.store', $kamar), $this->data($o))->assertRedirect();

        return Booking::latest('id_booking')->firstOrFail();
    }

    private function bukti(): UploadedFile
    {
        return UploadedFile::fake()->image('bukti.jpg');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    // ---------- tahan kamar otomatis ----------

    public function test_identitas_terkirim_membuat_kamar_otomatis_dipesan_selama_24_jam(): void
    {
        $kamar = $this->kamar();

        $booking = $this->booking($kamar);

        $this->assertSame('reservasi', $kamar->fresh()->status_kamar);
        $this->assertSame(Booking::MENUNGGU, $booking->status_booking);
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $booking->batas_waktu->timestamp, 5);
        $this->assertSame(0, Pembayaran::count(), 'Belum ada pembayaran');
    }

    public function test_orang_kedua_tidak_bisa_memesan_kamar_yang_sudah_ditahan(): void
    {
        $kamar = $this->kamar();
        $this->booking($kamar);

        $this->post(route('booking.store', $kamar), $this->data(['nama_pendaftar' => 'Siti', 'no_ktp' => '3303010101010002']))
            ->assertRedirect(route('kamar.index'))->assertSessionHas('error');

        $this->assertSame(1, Booking::count());
    }

    public function test_ubah_data_memperbarui_booking_yang_sama(): void
    {
        $kamar = $this->kamar();
        $booking = $this->booking($kamar);

        $this->get(route('kamar.show', [$kamar, 'booking' => $booking->token]))
            ->assertInertia(fn (Assert $p) => $p
                ->where('tersedia', true)->where('prefill.nama_pendaftar', 'Budi Santoso')->where('prefill.kontak', '081234567890'));

        $this->post(route('booking.store', $kamar), $this->data(['nama_pendaftar' => 'Budi S.', 'booking' => $booking->token]))->assertRedirect();

        $this->assertSame(1, Booking::count());
        $this->assertSame('Budi S.', $booking->fresh()->nama_pendaftar);

        // token milik kamar lain tidak dipakai untuk prefill
        $lain = $this->kamar('999');
        $this->get(route('kamar.show', [$lain, 'booking' => $booking->token]))->assertInertia(fn (Assert $p) => $p->where('prefill', null));
    }

    public function test_validasi_form_booking(): void
    {
        $kamar = $this->kamar();

        $this->post(route('booking.store', $kamar), $this->data([
            'no_ktp' => '123', 'kontak' => 'abc', 'tanggal_booking' => now()->subDay()->toDateString(),
        ]))->assertSessionHasErrors(['no_ktp', 'kontak', 'tanggal_booking']);

        $this->assertSame(0, Booking::count());
        $this->assertSame('kosong', $kamar->fresh()->status_kamar, 'Data tidak valid tidak boleh menahan kamar');
    }

    public function test_kamar_terisi_tidak_bisa_dibooking(): void
    {
        $kamar = $this->kamar('201', 'terisi');

        $this->post(route('booking.store', $kamar), $this->data())->assertRedirect(route('kamar.index'))->assertSessionHas('error');

        $this->assertSame(0, Booking::count());
    }

    public function test_url_booking_memakai_token_bukan_id(): void
    {
        $booking = $this->booking($this->kamar());

        $this->get('/booking/' . $booking->id_booking)->assertNotFound();
        $this->get(route('booking.review', $booking))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Booking/Review')->where('booking.nama', 'Budi Santoso')
            ->where('booking.ktp_samaran', fn ($k) => ! str_contains($k, '33030101') && str_ends_with($k, '0001')));
    }

    public function test_ktp_tersimpan_terenkripsi(): void
    {
        $booking = $this->booking($this->kamar());

        $mentah = \DB::table('booking')->where('id_booking', $booking->id_booking)->value('no_ktp');
        $this->assertNotSame('3303010101010001', $mentah);
        $this->assertSame('3303010101010001', $booking->fresh()->no_ktp);
    }

    // ---------- pembayaran: transfer / QRIS ----------

    public function test_transfer_wajib_foto_bukti_dan_bukti_tersimpan_privat(): void
    {
        Storage::fake('local');
        $booking = $this->booking($this->kamar());

        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Transfer Bank'])->assertSessionHasErrors('bukti_bayar');
        $this->assertSame(0, Pembayaran::count());

        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Transfer Bank', 'bukti_bayar' => $this->bukti()])
            ->assertRedirect(route('booking.payment', $booking));

        $p = Pembayaran::firstOrFail();
        $this->assertSame(Pembayaran::MENUNGGU, $p->status_pembayaran);
        $this->assertSame(800000, $p->jumlah_bayar);
        $this->assertSame(Booking::DIBAYAR, $booking->fresh()->status_booking);
        $this->assertNull($booking->fresh()->batas_waktu, 'Transfer menunggu admin, tidak ada batas hangus');
        $this->assertFalse(Penghuni::firstOrFail()->status_aktif);
        Storage::disk('local')->assertExists($p->bukti_bayar);
    }

    public function test_qris_wajib_foto_bukti(): void
    {
        $booking = $this->booking($this->kamar());

        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'QRIS'])->assertSessionHasErrors('bukti_bayar');

        Storage::fake('local');
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'QRIS', 'bukti_bayar' => $this->bukti()])->assertSessionHasNoErrors();
        $this->assertSame('QRIS', Pembayaran::firstOrFail()->metode_pembayaran);
    }

    public function test_bukti_harus_gambar(): void
    {
        $booking = $this->booking($this->kamar());

        $this->post(route('booking.pay', $booking), [
            'metode_pembayaran' => 'Transfer Bank',
            'bukti_bayar'       => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('bukti_bayar');
    }

    public function test_halaman_pembayaran_menampilkan_rekening_qris_dan_tombol_cetak(): void
    {
        Storage::fake('local');
        KosInfo::create([
            'nama_kos' => 'Asri', 'kontak_pemilik' => '081234567890', 'peraturan_kos' => '-', 'lokasi' => 'Purbalingga',
            'info_pembayaran' => 'BCA 123 a.n. Pemilik', 'foto_qris' => 'kos/qris.png',
        ]);
        $booking = $this->booking($this->kamar());

        $this->get(route('booking.payment', $booking))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Booking/Payment')
            ->where('bayar.info_transfer', 'BCA 123 a.n. Pemilik')->where('bayar.qris', '/storage/kos/qris.png')
            ->where('bayar.jam_tahan', 24)->where('pembayaran', null)
            ->where('booking.kode', $booking->kode)->where('booking.batas_waktu', fn ($v) => $v !== null));

        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Transfer Bank', 'bukti_bayar' => $this->bukti()]);
        $pembayaran = Pembayaran::firstOrFail();

        // setelah bayar: tombol cetak bukti pembayaran tersedia di halaman pembayaran
        $this->get(route('booking.payment', $booking))->assertInertia(fn (Assert $p) => $p
            ->where('pembayaran.status', 'Menunggu Konfirmasi')
            ->where('pembayaran.urls.cetak', route('booking.bukti', [$pembayaran, 'cetak' => 1])));

        $this->get(route('booking.bukti', $pembayaran))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Booking/Receipt')->where('pembayaran.kode', $booking->kode)->where('pembayaran.nama', 'Budi Santoso')
            ->where('pembayaran.kamar', '101')->where('cetak', false));

        $this->get(route('booking.bukti', [$pembayaran, 'cetak' => 1]))->assertInertia(fn (Assert $p) => $p->where('cetak', true));
    }

    // ---------- pembayaran: bayar langsung ----------

    public function test_bayar_langsung_tanpa_bukti_dan_batas_datang_24_jam(): void
    {
        $booking = $this->booking($this->kamar());

        $this->travel(5)->hours();
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Bayar Langsung'])->assertRedirect(route('booking.payment', $booking));

        $p = Pembayaran::firstOrFail();
        $this->assertSame('Bayar Langsung', $p->metode_pembayaran);
        $this->assertNull($p->bukti_bayar);
        $this->assertSame(Booking::DIBAYAR, $booking->fresh()->status_booking);
        // 24 jam dihitung sejak memilih bayar langsung
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $booking->fresh()->batas_waktu->timestamp, 5);
    }

    public function test_pembayaran_tidak_bisa_dikirim_dua_kali(): void
    {
        $booking = $this->booking($this->kamar());

        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Bayar Langsung']);
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Bayar Langsung'])->assertSessionHas('error');

        $this->assertSame(1, Pembayaran::count());
        $this->assertSame(1, Penghuni::count());
    }

    public function test_ktp_yang_sama_memakai_satu_data_penghuni(): void
    {
        $a = $this->booking($this->kamar('101'));
        $this->post(route('booking.pay', $a), ['metode_pembayaran' => 'Bayar Langsung']);
        app(BookingService::class)->tolak(Pembayaran::first()->id_pembayaran, 'tes');

        $b = $this->booking($this->kamar('102'), ['kontak' => '081299998888']);
        $this->post(route('booking.pay', $b), ['metode_pembayaran' => 'Bayar Langsung']);

        $this->assertSame(1, Penghuni::count());
        $this->assertSame('081299998888', Penghuni::first()->kontak);
    }

    public function test_metode_tidak_dikenal_ditolak(): void
    {
        $booking = $this->booking($this->kamar());

        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Kartu Kredit'])->assertSessionHasErrors('metode_pembayaran');
    }

    // ---------- hangus otomatis ----------

    public function test_booking_tanpa_pembayaran_hangus_setelah_24_jam_dan_kamar_dibuka(): void
    {
        $kamar = $this->kamar();
        $booking = $this->booking($kamar);

        $this->travel(23)->hours();
        $this->assertSame(0, app(BookingService::class)->hanguskanKedaluwarsa());
        $this->assertSame('reservasi', $kamar->fresh()->status_kamar);

        $this->travel(2)->hours();
        $this->assertSame(1, app(BookingService::class)->hanguskanKedaluwarsa());

        $this->assertSame(Booking::HANGUS, $booking->fresh()->status_booking);
        $this->assertSame('kosong', $kamar->fresh()->status_kamar);
    }

    public function test_bayar_langsung_yang_tidak_datang_hangus_otomatis(): void
    {
        $kamar = $this->kamar();
        $booking = $this->booking($kamar);
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Bayar Langsung']);

        $this->travel(25)->hours();
        $this->artisan('booking:kedaluwarsa')->assertSuccessful();

        $this->assertSame(Booking::HANGUS, $booking->fresh()->status_booking);
        $this->assertSame(Pembayaran::HANGUS, Pembayaran::firstOrFail()->status_pembayaran);
        $this->assertSame('kosong', $kamar->fresh()->status_kamar);

        $this->get(route('booking.payment', $booking))->assertInertia(fn (Assert $p) => $p
            ->where('booking.status', 'Hangus')->where('pembayaran.status', 'Hangus'));
    }

    public function test_transfer_yang_menunggu_admin_tidak_hangus(): void
    {
        Storage::fake('local');
        $kamar = $this->kamar();
        $booking = $this->booking($kamar);
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Transfer Bank', 'bukti_bayar' => $this->bukti()]);

        $this->travel(72)->hours();

        $this->assertSame(0, app(BookingService::class)->hanguskanKedaluwarsa());
        $this->assertSame(Booking::DIBAYAR, $booking->fresh()->status_booking);
        $this->assertSame('reservasi', $kamar->fresh()->status_kamar);
    }

    public function test_hangus_tidak_mengubah_kamar_yang_sudah_diatur_admin(): void
    {
        $kamar = $this->kamar();
        $this->booking($kamar);
        $kamar->update(['status_kamar' => 'terisi']); // admin menandai terisi secara manual

        $this->travel(25)->hours();
        app(BookingService::class)->hanguskanKedaluwarsa();

        $this->assertSame('terisi', $kamar->fresh()->status_kamar);
    }

    public function test_tidak_bisa_membayar_setelah_hangus(): void
    {
        $kamar = $this->kamar();
        $booking = $this->booking($kamar);

        $this->travel(25)->hours();
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Bayar Langsung'])->assertSessionHas('error');

        $this->assertSame(0, Pembayaran::count());
        $this->assertSame(Booking::HANGUS, $booking->fresh()->status_booking);
    }

    public function test_request_web_memicu_pembersihan_tanpa_cron(): void
    {
        $kamar = $this->kamar();
        $booking = $this->booking($kamar);

        $this->travel(25)->hours();
        $this->get('/kamar')->assertOk();

        $this->assertSame(Booking::HANGUS, $booking->fresh()->status_booking);
        $this->assertSame('kosong', $kamar->fresh()->status_kamar);
    }

    public function test_kamar_bisa_dipesan_lagi_setelah_hangus(): void
    {
        $kamar = $this->kamar();
        $this->booking($kamar);

        $this->travel(25)->hours();

        $baru = $this->booking($kamar, ['nama_pendaftar' => 'Siti', 'no_ktp' => '3303010101010002']);
        $this->assertSame('reservasi', $kamar->fresh()->status_kamar);
        $this->assertSame('Siti', $baru->nama_pendaftar);
    }

    // ---------- bukti pembayaran ----------

    public function test_bukti_menjadi_lunas_setelah_admin_menyetujui(): void
    {
        $booking = $this->booking($this->kamar());
        $this->post(route('booking.pay', $booking), ['metode_pembayaran' => 'Bayar Langsung']);
        $pembayaran = Pembayaran::firstOrFail();

        app(BookingService::class)->setujui($pembayaran->id_pembayaran);

        $this->get(route('booking.bukti', $pembayaran))->assertInertia(fn (Assert $p) => $p
            ->where('pembayaran.status', 'Terkonfirmasi')->where('pembayaran.jumlah', 800000));
        $this->get(route('booking.payment', $booking))->assertInertia(fn (Assert $p) => $p->where('pembayaran.status', 'Terkonfirmasi'));
    }
}
