<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Models\KosInfo;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\SewaKamar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** Satu pembayaran "Menunggu Konfirmasi" lewat alur publik. */
    private function pembayaran(string $metode = 'Bayar Langsung', string $nomor = '101', string $ktp = '3303010101010001'): Pembayaran
    {
        Storage::fake('local');
        $kamar = Kamar::create(['nomor_kamar' => $nomor, 'biaya_sewa' => 800000, 'status_kamar' => 'kosong']);
        $this->post(route('booking.store', $kamar), [
            'nama_pendaftar' => 'Budi', 'no_ktp' => $ktp, 'kontak' => '081234567890',
            'tanggal_booking' => now()->addDay()->toDateString(),
        ]);
        $this->post(route('booking.pay', Booking::latest('id_booking')->firstOrFail()), [
            'metode_pembayaran' => $metode,
            'bukti_bayar'       => $metode === 'Bayar Langsung' ? null : UploadedFile::fake()->image('b.png'),
        ]);

        return Pembayaran::latest('id_pembayaran')->firstOrFail();
    }

    // ---------- akses ----------

    public function test_tamu_diarahkan_ke_login_untuk_semua_halaman_admin(): void
    {
        foreach (['/admin/dashboard', '/admin/kamar', '/admin/fasilitas', '/admin/penghuni', '/admin/penghuni/1',
                  '/admin/konfirmasi', '/admin/info-kos', '/admin/dashboard/export', '/admin/konfirmasi/1/bukti'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $this->post('/admin/konfirmasi/1/approve')->assertRedirect('/login');
        $this->delete('/admin/kamar/1')->assertRedirect('/login');
        $this->delete('/admin/penghuni/1')->assertRedirect('/login');
        $this->put('/admin/info-kos')->assertRedirect('/login');
    }

    public function test_user_non_admin_ditolak(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_login_admin_berhasil_dan_gagal(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'rahasia-123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'rahasia-123'])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_biasa_tidak_bisa_login_ke_panel(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_dibatasi_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'x@y.test', 'password' => 'salah']);
        }

        $this->post('/login', ['email' => 'x@y.test', 'password' => 'salah'])->assertStatus(429);
    }

    // ---------- halaman ----------

    public function test_semua_halaman_admin_terbuka(): void
    {
        $p = $this->pembayaran('Transfer Bank');
        $this->actingAs($this->admin());

        $this->get('/admin')->assertRedirect('/admin/dashboard');
        $this->get('/admin/dashboard')->assertInertia(fn (Assert $a) => $a->component('Admin/Dashboard'));
        $this->get('/admin/dashboard/export')->assertOk();
        $this->get('/admin/kamar')->assertInertia(fn (Assert $a) => $a->component('Admin/Kamar/Index'));
        $this->get('/admin/fasilitas')->assertInertia(fn (Assert $a) => $a->component('Admin/Fasilitas/Index'));
        $this->get('/admin/penghuni')->assertInertia(fn (Assert $a) => $a->component('Admin/Penghuni/Index')->where('penghunis.total', 1));
        $this->get('/admin/penghuni/' . Penghuni::first()->id_penghuni)->assertInertia(fn (Assert $a) => $a->component('Admin/Penghuni/Show'));
        $this->get('/admin/konfirmasi')->assertInertia(fn (Assert $a) => $a->component('Admin/Konfirmasi/Index')->has('pembayarans.data', 1));
        $this->get('/admin/konfirmasi?status=all&search=101')->assertInertia(fn (Assert $a) => $a->has('pembayarans.data', 1));
        $this->get('/admin/konfirmasi/' . $p->id_pembayaran)->assertInertia(fn (Assert $a) => $a->component('Admin/Konfirmasi/Show'));
        $this->get('/admin/info-kos')->assertInertia(fn (Assert $a) => $a->component('Admin/InfoKos/Edit'));
    }

    public function test_lencana_menunggu_konfirmasi_tersedia_untuk_admin(): void
    {
        $this->pembayaran();

        $this->actingAs($this->admin())->get('/admin/kamar')->assertInertia(fn (Assert $a) => $a->where('pending', 1));
    }

    // ---------- dashboard / visualisasi ----------

    public function test_dashboard_menyiapkan_data_grafik(): void
    {
        $p = $this->pembayaran('Transfer Bank');
        $this->actingAs($this->admin());
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve");
        Kamar::create(['nomor_kamar' => '102', 'biaya_sewa' => 1, 'status_kamar' => 'kosong']);

        $this->get('/admin/dashboard')->assertInertia(fn (Assert $a) => $a
            ->where('stat.totalKamar', 2)->where('stat.penghuniAktif', 1)->where('stat.menungguKonfirmasi', 0)
            ->where('stat.pemasukanBulanIni', 800000)->where('stat.pemasukanTahunIni', 800000)
            ->has('pemasukanBulanan', 12)->where('pemasukanBulanan.' . (now()->month - 1) . '.total', 800000)
            ->where('komposisiKamar.0.value', 1)->where('komposisiKamar.2.value', 1) // 1 tersedia, 1 terisi
            ->has('bookingHarian', 14)->where('bookingHarian.13.jumlah', 1)
            ->where('metodePembayaran.0.name', 'Transfer Bank')->where('metodePembayaran.0.value', 1)
            ->has('aktivitas', 1));
    }

    // ---------- konfirmasi pembayaran ----------

    public function test_setujui_membuat_kamar_terisi_dan_penghuni_aktif(): void
    {
        $p = $this->pembayaran();
        $this->actingAs($this->admin());

        $this->assertSame('reservasi', Kamar::first()->status_kamar);
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve")->assertRedirect('/admin/konfirmasi');

        $this->assertSame(Pembayaran::TERKONFIRMASI, $p->fresh()->status_pembayaran);
        $this->assertSame('terisi', Kamar::first()->status_kamar);
        $this->assertSame(Booking::TERKONFIRMASI, Booking::first()->status_booking);
        $this->assertTrue(Penghuni::first()->status_aktif);
    }

    public function test_setujui_dua_kali_ditolak(): void
    {
        $p = $this->pembayaran();
        $this->actingAs($this->admin());

        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve");
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve")->assertSessionHas('error');
    }

    public function test_tolak_menyimpan_alasan_dan_melepas_kamar(): void
    {
        $p = $this->pembayaran('Transfer Bank');
        $this->actingAs($this->admin());

        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/reject")->assertSessionHasErrors('alasan_penolakan');
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/reject", ['alasan_penolakan' => 'Nominal tidak sesuai'])->assertRedirect('/admin/konfirmasi');

        $p->refresh();
        $this->assertSame(Pembayaran::DITOLAK, $p->status_pembayaran);
        $this->assertSame('Nominal tidak sesuai', $p->alasan_penolakan);
        $this->assertSame('kosong', Kamar::first()->status_kamar);
        $this->assertSame(Booking::DITOLAK, Booking::first()->status_booking);
    }

    public function test_setujui_ditolak_bila_kamar_sudah_ditandai_terisi(): void
    {
        $p = $this->pembayaran();
        $this->actingAs($this->admin());
        Kamar::first()->update(['status_kamar' => 'terisi']);

        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve")->assertSessionHas('error');
        $this->assertSame(Pembayaran::MENUNGGU, $p->fresh()->status_pembayaran);
    }

    public function test_foto_bukti_transfer_hanya_dapat_dilihat_admin(): void
    {
        $p = $this->pembayaran('Transfer Bank');

        $this->get("/admin/konfirmasi/{$p->id_pembayaran}/bukti")->assertRedirect('/login');

        $this->actingAs($this->admin());
        $this->get("/admin/konfirmasi/{$p->id_pembayaran}/bukti")->assertOk();
        $this->get("/admin/konfirmasi/{$p->id_pembayaran}")->assertInertia(fn (Assert $a) => $a
            ->where('pembayaran.ada_bukti', true)->where('pembayaran.bukti_url', route('admin.konfirmasi.bukti', $p->id_pembayaran))
            ->where('pembayaran.penghuni.no_ktp', '3303010101010001'));
    }

    public function test_bayar_langsung_tidak_punya_foto_bukti(): void
    {
        $p = $this->pembayaran('Bayar Langsung');
        $this->actingAs($this->admin());

        $this->get("/admin/konfirmasi/{$p->id_pembayaran}/bukti")->assertNotFound();
        $this->get("/admin/konfirmasi/{$p->id_pembayaran}")->assertInertia(fn (Assert $a) => $a
            ->where('pembayaran.ada_bukti', false)->where('pembayaran.batas', fn ($b) => $b !== null));
    }

    public function test_filter_status_hangus_di_konfirmasi(): void
    {
        $p = $this->pembayaran('Bayar Langsung');
        $this->travel(25)->hours();
        app(\App\Services\BookingService::class)->hanguskanKedaluwarsa();
        $this->actingAs($this->admin());

        $this->get('/admin/konfirmasi?status=Hangus')->assertInertia(fn (Assert $a) => $a->has('pembayarans.data', 1)->where('pembayarans.data.0.status', 'Hangus'));
        $this->get('/admin/konfirmasi')->assertInertia(fn (Assert $a) => $a->has('pembayarans.data', 0));
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve")->assertSessionHas('error');
        $this->travelBack();
    }

    // ---------- kamar ----------

    public function test_crud_kamar_dengan_foto(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $wifi = Fasilitas::create(['nama_fasilitas' => 'Wi-Fi']);

        $this->post('/admin/kamar', [
            'nomor_kamar' => '301', 'biaya_sewa' => 900000, 'status_kamar' => 'kosong', 'detail_kamar' => 'Luas',
            'fasilitas' => [$wifi->id_fasilitas], 'foto_kamar' => UploadedFile::fake()->image('k.jpg'),
        ])->assertRedirect('/admin/kamar');

        $kamar = Kamar::firstOrFail();
        $this->assertCount(1, $kamar->fasilitas);
        Storage::disk('public')->assertExists($kamar->foto_kamar);
        $fotoLama = $kamar->foto_kamar;

        // ganti foto -> foto lama terhapus
        $this->put("/admin/kamar/{$kamar->id_kamar}", [
            'nomor_kamar' => '301', 'biaya_sewa' => 950000, 'status_kamar' => 'reservasi',
            'foto_kamar' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect('/admin/kamar');

        $kamar->refresh();
        Storage::disk('public')->assertMissing($fotoLama);
        Storage::disk('public')->assertExists($kamar->foto_kamar);
        $this->assertSame(950000, $kamar->biaya_sewa);
        $this->assertSame('reservasi', $kamar->status_kamar);
        $this->assertCount(0, $kamar->fasilitas);

        // hapus foto tanpa unggah baru
        $this->put("/admin/kamar/{$kamar->id_kamar}", ['nomor_kamar' => '301', 'biaya_sewa' => 950000, 'status_kamar' => 'kosong', 'hapus_foto' => true]);
        $this->assertNull($kamar->fresh()->foto_kamar);

        $this->delete("/admin/kamar/{$kamar->id_kamar}")->assertRedirect('/admin/kamar');
        $this->assertSame(0, Kamar::count());
    }

    public function test_admin_mengubah_status_kamar_cepat(): void
    {
        $this->actingAs($this->admin());
        $kamar = Kamar::create(['nomor_kamar' => '101', 'biaya_sewa' => 1, 'status_kamar' => 'kosong']);

        foreach (['terisi', 'reservasi', 'kosong'] as $status) {
            $this->patch("/admin/kamar/{$kamar->id_kamar}/status", ['status_kamar' => $status])->assertSessionHas('success');
            $this->assertSame($status, $kamar->fresh()->status_kamar);
        }

        $this->patch("/admin/kamar/{$kamar->id_kamar}/status", ['status_kamar' => 'rusak'])->assertSessionHasErrors('status_kamar');
    }

    public function test_validasi_kamar(): void
    {
        $this->actingAs($this->admin());
        Kamar::create(['nomor_kamar' => '101', 'biaya_sewa' => 1, 'status_kamar' => 'kosong']);

        $this->post('/admin/kamar', ['nomor_kamar' => '101', 'biaya_sewa' => 'abc', 'status_kamar' => 'rusak'])
            ->assertSessionHasErrors(['nomor_kamar', 'biaya_sewa', 'status_kamar']);
    }

    public function test_kamar_dengan_riwayat_tidak_bisa_dihapus(): void
    {
        $this->pembayaran();
        $this->actingAs($this->admin());

        $this->delete('/admin/kamar/' . Kamar::first()->id_kamar)->assertSessionHas('error');
        $this->assertSame(1, Kamar::count());
    }

    // ---------- fasilitas ----------

    public function test_admin_menambah_mengubah_dan_menghapus_fasilitas(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/fasilitas', ['nama_fasilitas' => 'Dapur Bersama', 'deskripsi_fasilitas' => 'Lengkap'])->assertRedirect('/admin/fasilitas');
        $f = Fasilitas::firstOrFail();
        $this->assertSame('Lengkap', $f->deskripsi_fasilitas);

        $this->post('/admin/fasilitas', ['nama_fasilitas' => 'Dapur Bersama'])->assertSessionHasErrors('nama_fasilitas'); // duplikat
        $this->post('/admin/fasilitas', ['nama_fasilitas' => ''])->assertSessionHasErrors('nama_fasilitas');

        // mengubah nama dengan nama sendiri tidak dianggap duplikat
        $this->put("/admin/fasilitas/{$f->id_fasilitas}", ['nama_fasilitas' => 'Dapur Bersama', 'deskripsi_fasilitas' => 'Baru'])->assertSessionHasNoErrors();
        $this->assertSame('Baru', $f->fresh()->deskripsi_fasilitas);

        $kamar = Kamar::create(['nomor_kamar' => '101', 'biaya_sewa' => 1, 'status_kamar' => 'kosong']);
        $kamar->fasilitas()->attach($f->id_fasilitas);
        $this->get('/admin/fasilitas')->assertInertia(fn (Assert $a) => $a->where('fasilitas.0.dipakai', 1));

        $this->delete("/admin/fasilitas/{$f->id_fasilitas}")->assertRedirect('/admin/fasilitas');
        $this->assertSame(0, Fasilitas::count());
        $this->assertCount(0, $kamar->fresh()->fasilitas);
    }

    // ---------- penghuni ----------

    public function test_admin_melihat_identitas_lengkap_penghuni(): void
    {
        $p = $this->pembayaran();
        $this->actingAs($this->admin());
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve");
        $penghuni = Penghuni::firstOrFail();

        $this->get("/admin/penghuni/{$penghuni->id_penghuni}")->assertInertia(fn (Assert $a) => $a
            ->where('penghuni.nama', 'Budi')->where('penghuni.no_ktp', '3303010101010001')->where('penghuni.kontak', '081234567890')
            ->where('penghuni.aktif', true)->where('bisaHapus', false)
            ->where('riwayat.0.kamar', '101')->where('riwayat.0.pembayaran.0.status', 'Terkonfirmasi'));
    }

    public function test_tandai_keluar_mengosongkan_kamar_lalu_penghuni_bisa_dihapus(): void
    {
        $p = $this->pembayaran();
        $this->actingAs($this->admin());
        $this->post("/admin/konfirmasi/{$p->id_pembayaran}/approve");
        $penghuni = Penghuni::firstOrFail();

        // masih aktif -> tidak boleh dihapus
        $this->delete("/admin/penghuni/{$penghuni->id_penghuni}")->assertSessionHas('error');
        $this->assertSame(1, Penghuni::count());

        $this->post("/admin/penghuni/{$penghuni->id_penghuni}/keluar")->assertSessionHas('success');
        $this->assertFalse($penghuni->fresh()->status_aktif);
        $this->assertSame('kosong', Kamar::first()->status_kamar);
        $this->assertNotNull(SewaKamar::first()->tanggal_keluar);

        $this->delete("/admin/penghuni/{$penghuni->id_penghuni}")->assertRedirect('/admin/penghuni');

        $this->assertSame(0, Penghuni::count());
        // catatan pembayaran tetap ada, identitas hilang
        $this->assertSame(1, Pembayaran::count());
        $this->assertNull(SewaKamar::first()->id_penghuni);
        $booking = Booking::first();
        $this->assertSame('Penghuni dihapus', $booking->nama_pendaftar);
        $this->assertSame('-', $booking->no_ktp);

        // halaman admin tetap bisa dibuka tanpa data penghuni
        $this->get("/admin/konfirmasi/{$p->id_pembayaran}")->assertInertia(fn (Assert $a) => $a->where('pembayaran.penghuni', null));
        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_penghuni_dengan_booking_berjalan_tidak_bisa_dihapus(): void
    {
        $this->pembayaran(); // Menunggu Konfirmasi -> penghuni belum aktif
        $this->actingAs($this->admin());
        $penghuni = Penghuni::firstOrFail();

        $this->delete("/admin/penghuni/{$penghuni->id_penghuni}")->assertSessionHas('error');
        $this->assertSame(1, Penghuni::count());
    }

    // ---------- info kos ----------

    public function test_admin_mengubah_info_kos_dan_foto(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->put('/admin/info-kos', [
            'nama_kos' => 'Asri Baru', 'deskripsi_kos' => 'Deskripsi baru', 'kontak_pemilik' => '082111222333',
            'lokasi' => 'Jl. Baru 5', 'peraturan_kos' => "Aturan 1\nAturan 2", 'info_pembayaran' => 'BRI 999 a.n. Pemilik',
            'foto_kos' => UploadedFile::fake()->image('kos.jpg'), 'foto_qris' => UploadedFile::fake()->image('qris.png'),
        ])->assertRedirect('/admin/info-kos');

        $kos = KosInfo::firstOrFail();
        $this->assertSame('Asri Baru', $kos->nama_kos);
        Storage::disk('public')->assertExists($kos->foto_kos);
        Storage::disk('public')->assertExists($kos->foto_qris);

        // tampil di website publik
        $this->get('/tentang')->assertInertia(fn (Assert $a) => $a
            ->where('info.deskripsi', 'Deskripsi baru')->where('info.peraturan', ['Aturan 1', 'Aturan 2'])->where('info.kontak', '082111222333'));
        $this->get('/')->assertInertia(fn (Assert $a) => $a
            ->where('kos.nama', 'Asri Baru')->where('kos.foto', $kos->foto_url)
            ->where('kos.wa', fn ($w) => str_contains($w, 'wa.me/6282111222333')));

        // hapus QRIS & ganti foto kos
        $qrisLama = $kos->foto_qris;
        $fotoLama = $kos->foto_kos;
        $this->put('/admin/info-kos', [
            'nama_kos' => 'Asri Baru', 'kontak_pemilik' => '082111222333', 'lokasi' => 'Jl. Baru 5',
            'hapus_foto_qris' => true, 'foto_kos' => UploadedFile::fake()->image('kos2.jpg'),
        ]);
        $kos->refresh();
        $this->assertNull($kos->foto_qris);
        Storage::disk('public')->assertMissing($qrisLama);
        Storage::disk('public')->assertMissing($fotoLama);
    }

    public function test_info_kos_pertama_kali_dibuat_dari_form(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/info-kos')->assertInertia(fn (Assert $a) => $a->where('kos.nama_kos', ''));
        $this->put('/admin/info-kos', ['nama_kos' => 'Kos Baru', 'kontak_pemilik' => '081234567890', 'lokasi' => 'Jl. A'])->assertRedirect();

        $this->assertSame(1, KosInfo::count());
    }

    public function test_validasi_info_kos(): void
    {
        $this->actingAs($this->admin());

        $this->put('/admin/info-kos', ['nama_kos' => '', 'kontak_pemilik' => 'abc', 'lokasi' => ''])
            ->assertSessionHasErrors(['nama_kos', 'kontak_pemilik', 'lokasi']);
    }

    public function test_info_kos_menyertakan_batas_unggah_dan_status_migrasi(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/info-kos')->assertInertia(fn (Assert $a) => $a
            ->where('perluMigrasi', false)
            ->where('batasUnggah', fn ($b) => is_int($b) && $b > 0 && $b <= 5 * 1024 * 1024));

        $this->assertLessThanOrEqual(5 * 1024 * 1024, \App\Support\Media::batasUnggah());
    }

    public function test_info_kos_unggahan_gagal_memberi_pesan_yang_jelas(): void
    {
        $this->actingAs($this->admin());
        $tmp = tempnam(sys_get_temp_dir(), 'foto');
        file_put_contents($tmp, 'x');
        // meniru file yang ditolak PHP karena melebihi upload_max_filesize
        $gagal = new UploadedFile($tmp, 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->from('/admin/info-kos')->put('/admin/info-kos', [
            'nama_kos' => 'Asri', 'kontak_pemilik' => '081234567890', 'lokasi' => 'Jl. A', 'foto_kos' => $gagal,
        ])->assertSessionHasErrors('foto_kos');

        $this->assertStringContainsString('upload_max_filesize', session('errors')->first('foto_kos'));
    }

    public function test_info_kos_memberi_tahu_bila_database_belum_dimigrasi_bukan_error_500(): void
    {
        $this->actingAs($this->admin());
        \Illuminate\Support\Facades\Schema::table('kos_info', fn ($t) => $t->dropColumn('foto_qris'));

        $this->get('/admin/info-kos')->assertInertia(fn (Assert $a) => $a->where('perluMigrasi', true));

        $this->from('/admin/info-kos')->put('/admin/info-kos', [
            'nama_kos' => 'Asri', 'kontak_pemilik' => '081234567890', 'lokasi' => 'Jl. A',
        ])->assertRedirect('/admin/info-kos')->assertSessionHas('error', fn ($e) => str_contains($e, 'php artisan migrate'));

        $this->assertSame(0, KosInfo::count());
    }

    public function test_info_kos_disimpan_lewat_jalur_browser_post_dengan_method_put(): void
    {
        $this->seed();
        $this->actingAs($this->admin());
        $kos = $this->get('/admin/info-kos', ['X-Inertia' => 'true'])->json('props.kos');

        // persis seperti form React: nilai dari server, _method, file kosong, boolean '0'
        $this->post('/admin/info-kos', [
            '_method' => 'put', 'nama_kos' => 'Nama Baru', 'deskripsi_kos' => $kos['deskripsi_kos'], 'kontak_pemilik' => $kos['kontak_pemilik'],
            'lokasi' => $kos['lokasi'], 'peraturan_kos' => $kos['peraturan_kos'], 'info_pembayaran' => $kos['info_pembayaran'],
            'foto_kos' => '', 'foto_qris' => '', 'hapus_foto_kos' => '0', 'hapus_foto_qris' => '0',
        ], ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])->assertRedirect('/admin/info-kos')->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', KosInfo::first()->nama_kos);
    }

    // ---- jalur fetch (Accept: application/json) yang dipakai form Info Kos ----

    private function dataKos(array $o = []): array
    {
        return array_merge([
            '_method' => 'put', 'nama_kos' => 'Kos Fetch', 'deskripsi_kos' => '', 'kontak_pemilik' => '081234567890',
            'lokasi' => 'Jl. Fetch 1', 'peraturan_kos' => 'Aturan A', 'info_pembayaran' => '', 'hapus_foto_kos' => '0', 'hapus_foto_qris' => '0',
        ], $o);
    }

    public function test_info_kos_fetch_json_berhasil_dengan_dan_tanpa_file(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $json = ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

        $this->post('/admin/info-kos', $this->dataKos(), $json)->assertOk()->assertJsonPath('message', 'Info kos berhasil diperbarui.');
        $this->assertSame('Kos Fetch', KosInfo::firstOrFail()->nama_kos);

        $this->post('/admin/info-kos', $this->dataKos(['nama_kos' => 'Kos Foto', 'foto_kos' => UploadedFile::fake()->image('k.jpg')]), $json)->assertOk();
        $kos = KosInfo::firstOrFail();
        $this->assertSame('Kos Foto', $kos->nama_kos);
        Storage::disk('public')->assertExists($kos->foto_kos);
    }

    public function test_info_kos_fetch_json_validasi_422_dan_belum_login_401_dan_belum_migrasi_409(): void
    {
        $json = ['Accept' => 'application/json'];

        $this->post('/admin/info-kos', $this->dataKos(), $json)->assertUnauthorized();   // bukan redirect HTML

        $this->actingAs($this->admin());
        $this->post('/admin/info-kos', $this->dataKos(['nama_kos' => '', 'kontak_pemilik' => 'abc']), $json)
            ->assertStatus(422)->assertJsonValidationErrors(['nama_kos', 'kontak_pemilik']);

        \Illuminate\Support\Facades\Schema::table('kos_info', fn ($t) => $t->dropColumn('foto_qris'));
        $this->post('/admin/info-kos', $this->dataKos(), $json)->assertStatus(409)->assertJsonPath('message', fn ($m) => str_contains($m, 'php artisan migrate'));
    }
}
