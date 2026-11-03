import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Save } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Kolom, UnggahFoto } from '../../../components/admin/Bagian';
import { kirimForm } from '../../../lib/kirimForm';

function Bagian({ judul, sub, children }) {
    return (
        <section className="a-card p-6">
            <h2 className="text-base font-bold text-slate-900">{judul}</h2>
            {sub && <p className="text-xs text-slate-400">{sub}</p>}
            <div className="mt-5 space-y-5">{children}</div>
        </section>
    );
}

export default function Edit({ kos, perluMigrasi, batasUnggah }) {
    const atas = useRef(null);
    const [data, setData] = useState({
        nama_kos: kos.nama_kos,
        deskripsi_kos: kos.deskripsi_kos,
        kontak_pemilik: kos.kontak_pemilik,
        lokasi: kos.lokasi,
        peraturan_kos: kos.peraturan_kos,
        info_pembayaran: kos.info_pembayaran,
        foto_kos: null,
        foto_qris: null,
        hapus_foto_kos: false,
        hapus_foto_qris: false,
    });
    const [errors, setErrors] = useState({});
    const [hasil, setHasil] = useState(null); // { ok, pesan, detail, jenis }
    const [proses, setProses] = useState(false);

    const ubah = (k, v) => setData((d) => ({ ...d, [k]: v }));
    const set = (k) => (e) => ubah(k, e.target.value);
    const daftarError = Object.values(errors);

    // Gulir ke atas agar pesan hasil (berhasil/gagal) pasti terlihat.
    useEffect(() => {
        if (hasil || daftarError.length > 0) atas.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, [hasil, daftarError.length]);

    const kirim = async (e) => {
        e.preventDefault();
        setProses(true);
        setHasil(null);
        setErrors({});

        const r = await kirimForm('/admin/info-kos', data, 'PUT');
        setProses(false);

        if (r.ok) {
            setHasil({ ok: true, pesan: 'Perubahan Info Kos berhasil disimpan.' });
            setData((d) => ({ ...d, foto_kos: null, foto_qris: null, hapus_foto_kos: false, hapus_foto_qris: false }));
            router.reload({ preserveScroll: true }); // segarkan foto & nama di panel
        } else if (r.jenis === 'validasi') {
            setErrors(r.errors);
        } else {
            setHasil({ ok: false, pesan: r.pesan, detail: r.detail, status: r.status, jenis: r.jenis });
        }
    };

    return (
        <form onSubmit={kirim} className="mx-auto max-w-4xl space-y-6">
            <Head title="Info Kos" />
            <div ref={atas} className="scroll-mt-24" />

            {perluMigrasi && (
                <div className="flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <AlertTriangle size={18} className="mt-0.5 shrink-0" />
                    <p><b>Database belum diperbarui.</b> Jalankan <code className="rounded bg-amber-100 px-1.5 py-0.5">php artisan migrate</code> di folder proyek, lalu muat ulang halaman ini.</p>
                </div>
            )}

            {hasil?.ok && (
                <div role="status" className="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                    <CheckCircle2 size={18} /> {hasil.pesan}
                </div>
            )}

            {hasil && !hasil.ok && (
                <div role="alert" className="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800">
                    <p className="font-bold">Perubahan GAGAL disimpan{hasil.status ? ` (kode ${hasil.status})` : ''}.</p>
                    <p className="mt-1 break-words">{hasil.pesan}</p>
                    {hasil.detail && <p className="mt-1 break-words font-mono text-xs">{hasil.detail}</p>}
                    <p className="mt-2 text-xs text-rose-600">Salin pesan di atas bila perlu bantuan. Detail lengkap ada di <code>storage/logs/laravel.log</code>.</p>
                </div>
            )}

            {daftarError.length > 0 && (
                <div role="alert" className="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p className="font-bold">Perubahan belum tersimpan. Periksa isian berikut:</p>
                    <ul className="mt-1.5 list-inside list-disc space-y-0.5">{daftarError.map((m, i) => <li key={i}>{m}</li>)}</ul>
                </div>
            )}

            <Bagian judul="Informasi Umum" sub="Tampil di beranda, halaman Tentang Kos, dan tombol WhatsApp.">
                <div className="grid gap-5 sm:grid-cols-2">
                    <Kolom label="Nama kos" error={errors.nama_kos}><input value={data.nama_kos} onChange={set('nama_kos')} required className="a-field" /></Kolom>
                    <Kolom label="Kontak pemilik (opsional)" error={errors.kontak_pemilik}>
                        <input value={data.kontak_pemilik} onChange={set('kontak_pemilik')} className="a-field" placeholder="Boleh dikosongkan" />
                        <p className="mt-1 text-[11px] text-slate-400">Hanya untuk tombol "Hubungi Kami" di website. Kabar setuju/tolak dikirim ke nomor penyewa, bukan ke sini.</p>
                    </Kolom>
                </div>
                <Kolom label="Alamat / lokasi" error={errors.lokasi}><input value={data.lokasi} onChange={set('lokasi')} required className="a-field" placeholder="Jl. Contoh No. 12, Kota" /></Kolom>
                <Kolom label="Deskripsi kos" error={errors.deskripsi_kos}>
                    <textarea rows={4} value={data.deskripsi_kos} onChange={set('deskripsi_kos')} className="a-field" placeholder="Ceritakan suasana dan keunggulan kos Anda (tampil di halaman Tentang Kos)" />
                </Kolom>
            </Bagian>

            <Bagian judul="Peraturan Kos" sub="Tulis satu aturan per baris.">
                <Kolom label="Peraturan" error={errors.peraturan_kos}>
                    <textarea rows={6} value={data.peraturan_kos} onChange={set('peraturan_kos')} className="a-field" placeholder={'Jaga kebersihan kamar\nTamu wajib lapor\nJam tenang pukul 22.00'} />
                </Kolom>
            </Bagian>

            <Bagian judul="Foto" sub="Dipakai sebagai foto utama di website. Kamar yang punya foto sendiri memakai fotonya masing-masing.">
                <UnggahFoto label="Foto kamar / kos utama" url={kos.foto_url} file={data.foto_kos} onPilih={(f) => ubah('foto_kos', f)}
                    hapus={data.hapus_foto_kos} onHapus={(v) => ubah('hapus_foto_kos', v)} error={errors.foto_kos} batas={batasUnggah} />
            </Bagian>

            <Bagian judul="Pembayaran" sub="Ditampilkan ke calon penghuni saat memilih transfer atau QRIS.">
                <Kolom label="Rekening transfer" error={errors.info_pembayaran}>
                    <textarea rows={4} value={data.info_pembayaran} onChange={set('info_pembayaran')} className="a-field" placeholder={'BCA 1234567890\na.n. Nama Pemilik'} />
                </Kolom>
                <UnggahFoto label="Gambar QRIS" url={kos.qris_url} file={data.foto_qris} onPilih={(f) => ubah('foto_qris', f)}
                    hapus={data.hapus_foto_qris} onHapus={(v) => ubah('hapus_foto_qris', v)} error={errors.foto_qris} batas={batasUnggah} />
            </Bagian>

            <div className="sticky bottom-4 flex justify-end">
                <button type="submit" disabled={proses} className="a-btn-primary shadow-lg"><Save size={16} /> {proses ? 'Menyimpan...' : 'Simpan Perubahan'}</button>
            </div>
        </form>
    );
}

Edit.layout = (page) => <AdminLayout title="Info Kos">{page}</AdminLayout>;
