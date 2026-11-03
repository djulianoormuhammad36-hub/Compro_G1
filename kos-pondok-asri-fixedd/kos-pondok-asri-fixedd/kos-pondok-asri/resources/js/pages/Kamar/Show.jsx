import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import PublicLayout from '../../components/public/PublicLayout';
import { FotoKamar, StatusKamar } from '../../components/public/Foto';
import { Breadcrumb } from '../../components/public/Bagian';
import { rupiah } from '../../lib/utils';

function Isian({ label, name, form, children, ...props }) {
    return (
        <div>
            <label className="label" htmlFor={name}>{label}</label>
            <input id={name} name={name} value={form.data[name]} onChange={(e) => form.setData(name, e.target.value)} required className="field" {...props} />
            {form.errors[name] && <p className="mt-1 text-xs text-rose-600">{form.errors[name]}</p>}
        </div>
    );
}

export default function Show({ kamar, tersedia, prefill, wa, hariIni }) {
    const form = useForm({
        booking: prefill?.token ?? '',
        nama_pendaftar: prefill?.nama_pendaftar ?? '',
        no_ktp: prefill?.no_ktp ?? '',
        kontak: prefill?.kontak ?? '',
        tanggal_booking: prefill?.tanggal_booking ?? '',
    });

    const kirim = (e) => {
        e.preventDefault();
        form.post(`/kamar/${kamar.id}/booking`);
    };

    return (
        <div className="mx-auto max-w-6xl px-5 py-10">
            <Head title={`Kamar ${kamar.nomor}`} />
            <Breadcrumb items={[['Beranda', '/'], ['Kamar', '/kamar'], [`Kamar ${kamar.nomor}`]]} />

            <section className="mt-6 grid gap-10 lg:grid-cols-2">
                <div>
                    <FotoKamar src={kamar.foto} className="h-72 w-full rounded-2xl md:h-[340px]" />
                    <div className="mt-3 grid grid-cols-3 gap-3">
                        {[1, 2, 3].map((i) => <FotoKamar key={i} src={kamar.foto} className="h-20 w-full rounded-xl md:h-[68px]" />)}
                    </div>
                </div>

                <div>
                    <p className="eyebrow !text-ruma-ink">Kamar {kamar.nomor}</p>
                    <h1 className="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ruma-ink md:text-[34px]">Kamar nyaman untuk mendukung aktivitasmu</h1>
                    <p className="mt-6 text-2xl font-extrabold text-ruma-green">{rupiah(kamar.biaya)} <span className="ml-1 text-xs font-medium text-ruma-mute">/ bulan</span></p>
                    <StatusKamar status={kamar.status} className="mt-4" />
                    <p className="mt-5 whitespace-pre-line text-sm leading-relaxed text-ruma-body">
                        {kamar.detail || 'Ruang tinggal yang rapi dan nyaman untuk mendukung aktivitas kuliah maupun kerja sehari-hari.'}
                    </p>

                    <div className="mt-8 border-t border-ruma-line pt-6">
                        <h2 className="text-sm font-bold text-ruma-ink">Fasilitas kamar</h2>
                        <ul className="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-xs text-ruma-body">
                            {kamar.fasilitas.length > 0 ? kamar.fasilitas.map((f) => (
                                <li key={f.id} className="flex items-center gap-2"><Check size={12} className="text-ruma-green" />{f.nama}</li>
                            )) : <li className="col-span-2 text-ruma-mute">Fasilitas standar kamar.</li>}
                        </ul>
                    </div>
                </div>
            </section>

            <section className="mt-12 border-t border-ruma-line pt-10">
                <h2 className="text-2xl font-extrabold tracking-tight text-ruma-ink">Ajukan booking kamar</h2>
                <p className="mt-1.5 text-xs text-ruma-mute">
                    Tanpa daftar akun. Begitu data terkirim, kamar otomatis ditahan untukmu selama 24 jam.
                </p>

                <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                    <div className="card p-6 md:p-7">
                        {tersedia ? (
                            <form onSubmit={kirim} className="space-y-5">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <Isian label="Nama lengkap" name="nama_pendaftar" form={form} placeholder="Masukkan nama sesuai identitas" />
                                    <Isian label="Nomor KTP" name="no_ktp" form={form} inputMode="numeric" maxLength={16} placeholder="Masukkan nomor KTP (16 digit)" />
                                    <Isian label="Nomor handphone" name="kontak" form={form} inputMode="tel" placeholder="Contoh: 08xxxxxxxxxx" />
                                    <Isian label="Tanggal booking" name="tanggal_booking" form={form} type="date" min={hariIni} />
                                </div>

                                <label className="flex items-start gap-2.5 text-xs text-ruma-body">
                                    <input type="checkbox" required className="mt-0.5 rounded border-ruma-line text-ruma-green focus:ring-ruma-green/30" />
                                    Saya telah memeriksa data yang dimasukkan.
                                </label>
                                <p className="text-[10px] text-ruma-mute">Data pribadi digunakan untuk keperluan booking sesuai kebijakan pengelola kos.</p>

                                <button type="submit" disabled={form.processing} className="btn-primary">
                                    {form.processing ? 'Memproses...' : 'Lanjutkan ke Review'}
                                </button>
                            </form>
                        ) : (
                            <>
                                <p className="text-sm font-semibold text-ruma-ink">Kamar ini sedang tidak tersedia untuk dibooking.</p>
                                <p className="mt-1 text-xs text-ruma-mute">Silakan pilih kamar lain yang masih tersedia.</p>
                                <Link href="/kamar?status=kosong" className="btn-primary mt-5">Lihat kamar tersedia</Link>
                            </>
                        )}
                    </div>

                    <aside className="rounded-2xl bg-ruma-tint p-6">
                        <h3 className="text-sm font-bold text-ruma-ink">Ringkasan booking</h3>
                        <div className="mt-4 border-t border-ruma-green/10 pt-4">
                            <p className="text-xs text-ruma-body">Kamar {kamar.nomor}</p>
                            <p className="mt-2 text-sm font-extrabold text-ruma-green">{rupiah(kamar.biaya)} / bulan</p>
                            <p className="mt-5 text-[11px] leading-relaxed text-ruma-body">
                                Kamar ditahan 24 jam setelah data dikirim. Jika tidak dibayar, booking hangus otomatis dan kamar dibuka kembali.
                            </p>
                        </div>
                        <div className="mt-10 flex items-center gap-3 text-[11px] text-ruma-body">
                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white font-bold text-ruma-ink">i</span>
                            <span>
                                Butuh info lebih lanjut?{' '}
                                {wa ? <a href={wa} target="_blank" rel="noopener noreferrer" className="font-semibold text-ruma-green hover:underline">Hubungi pemilik kos via WhatsApp.</a> : 'Hubungi pemilik kos.'}
                            </span>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    );
}

Show.layout = (page) => <PublicLayout>{page}</PublicLayout>;
