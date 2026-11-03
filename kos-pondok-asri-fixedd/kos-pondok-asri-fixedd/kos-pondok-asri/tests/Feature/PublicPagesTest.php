<?php

namespace Tests\Feature;

use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Models\KosInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function kos(array $o = []): KosInfo
    {
        return KosInfo::create($o + [
            'nama_kos' => 'Pondok Asri', 'kontak_pemilik' => '081234567890',
            'peraturan_kos' => "Jaga kebersihan.\nHormati penghuni lain.", 'lokasi' => 'Jl. Contoh 1, Purbalingga',
        ]);
    }

    public function test_beranda_dan_katalog_tampil_tanpa_data(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Home')->where('kamarList', [])->where('hargaMulai', null));
        $this->get('/kamar')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Kamar/Index')->where('kamars.total', 0));
    }

    public function test_katalog_memfilter_dan_mengurutkan(): void
    {
        Kamar::create(['nomor_kamar' => 'A1', 'biaya_sewa' => 900000, 'status_kamar' => 'kosong']);
        Kamar::create(['nomor_kamar' => 'B2', 'biaya_sewa' => 500000, 'status_kamar' => 'terisi']);

        $this->get('/kamar?status=kosong')->assertInertia(fn (Assert $p) => $p
            ->has('kamars.data', 1)->where('kamars.data.0.nomor', 'A1')->where('filters.status', 'kosong'));

        $this->get('/kamar?sort=lowest')->assertInertia(fn (Assert $p) => $p
            ->where('kamars.data.0.nomor', 'B2')->where('kamars.data.1.nomor', 'A1'));

        $this->get('/kamar?search=B')->assertInertia(fn (Assert $p) => $p
            ->has('kamars.data', 1)->where('kamars.data.0.nomor', 'B2'));
    }

    public function test_detail_kamar_tersedia_dan_404(): void
    {
        $kamar = Kamar::create(['nomor_kamar' => 'A1', 'biaya_sewa' => 900000, 'status_kamar' => 'kosong']);

        $this->get("/kamar/{$kamar->id_kamar}")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Kamar/Show')->where('tersedia', true)->where('kamar.nomor', 'A1')->where('prefill', null));

        $this->get('/kamar/9999')->assertNotFound();
    }

    public function test_kamar_yang_tidak_kosong_tidak_bisa_dibooking(): void
    {
        foreach (['terisi', 'reservasi'] as $i => $status) {
            $kamar = Kamar::create(['nomor_kamar' => "T{$i}", 'biaya_sewa' => 1, 'status_kamar' => $status]);

            $this->get("/kamar/{$kamar->id_kamar}")->assertInertia(fn (Assert $p) => $p->where('tersedia', false));
        }
    }

    public function test_beranda_menampilkan_harga_mulai_fasilitas_dan_whatsapp(): void
    {
        $this->kos(['nama_kos' => 'Ruma']);
        Fasilitas::create(['nama_fasilitas' => 'Wi-Fi', 'deskripsi_fasilitas' => 'Internet cepat']);
        Kamar::create(['nomor_kamar' => '01', 'biaya_sewa' => 1200000, 'status_kamar' => 'kosong']);
        Kamar::create(['nomor_kamar' => '02', 'biaya_sewa' => 900000, 'status_kamar' => 'terisi']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('hargaMulai', 'Rp 1,2 jt')            // dihitung dari kamar yang tersedia
            ->where('fasilitasList.0.nama', 'Wi-Fi')
            ->where('fasilitasList.0.ikon', 'fa-wifi')
            ->where('kos.nama', 'Ruma')
            // klik WhatsApp -> chat ke nomor pemilik kos
            ->where('kos.wa', fn ($wa) => str_starts_with($wa, 'https://wa.me/6281234567890?text=')));
    }

    public function test_nomor_whatsapp_dinormalisasi(): void
    {
        foreach (['081234567890', '+62 812-3456-7890', '6281234567890'] as $nomor) {
            $kos = new KosInfo(['kontak_pemilik' => $nomor]);
            $this->assertSame('https://wa.me/6281234567890', $kos->whatsapp_url, $nomor);
        }

        $this->assertNull((new KosInfo(['kontak_pemilik' => '']))->whatsapp_url);
    }

    public function test_detail_kamar_menyertakan_wa_dengan_nomor_kamar(): void
    {
        $this->kos();
        $kamar = Kamar::create(['nomor_kamar' => 'A1', 'biaya_sewa' => 1, 'status_kamar' => 'kosong']);

        $this->get("/kamar/{$kamar->id_kamar}")->assertInertia(fn (Assert $p) => $p
            ->where('wa', fn ($wa) => str_contains($wa, 'wa.me/6281234567890') && str_contains($wa, rawurlencode('kamar A1'))));
    }

    public function test_halaman_tentang_kos_menampilkan_info_dari_admin(): void
    {
        $this->kos(['deskripsi_kos' => 'Kos tenang dekat kampus.']);

        $this->get('/tentang')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Tentang')
            ->where('info.deskripsi', 'Kos tenang dekat kampus.')
            ->where('info.peraturan', ['Jaga kebersihan.', 'Hormati penghuni lain.'])
            ->where('info.lokasi', 'Jl. Contoh 1, Purbalingga')
            ->where('info.maps', fn ($m) => str_contains($m, 'google.com/maps')));

        KosInfo::query()->delete();
        $this->get('/tentang')->assertOk()->assertInertia(fn (Assert $p) => $p->where('info', null));
    }

    public function test_foto_kamar_memakai_foto_sendiri(): void
    {
        $kamar = Kamar::create(['nomor_kamar' => 'A1', 'biaya_sewa' => 1, 'status_kamar' => 'kosong', 'foto_kamar' => 'kamar/a1.jpg']);

        $this->get("/kamar/{$kamar->id_kamar}")->assertInertia(fn (Assert $p) => $p->where('kamar.foto', '/storage/kamar/a1.jpg'));
    }
}
