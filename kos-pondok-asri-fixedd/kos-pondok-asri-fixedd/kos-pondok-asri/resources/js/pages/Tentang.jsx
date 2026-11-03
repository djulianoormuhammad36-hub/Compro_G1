import { Head, Link } from '@inertiajs/react';
import { Check, LocateFixed } from 'lucide-react';
import PublicLayout from '../components/public/PublicLayout';
import { FotoKamar } from '../components/public/Foto';

export default function Tentang({ info }) {
    const nama = info?.nama ?? 'Kos';

    return (
        <div className="mx-auto max-w-6xl px-5 py-10">
            <Head title="Tentang Kos" />

            <h1 className="text-3xl font-extrabold tracking-tight text-ruma-ink md:text-4xl">Kenalan lebih dekat dengan {nama}</h1>
            <p className="mt-2 text-sm text-ruma-mute">Hunian nyaman, aman, dan terasa dekat untuk mendukung keseharianmu.</p>

            <section className="mt-8 grid gap-6 md:grid-cols-2">
                <FotoKamar className="h-72 w-full rounded-2xl md:h-full md:min-h-[280px]" />
                <div className="card p-7">
                    <p className="eyebrow">Cerita {nama}</p>
                    <h2 className="mt-4 text-2xl font-extrabold leading-snug tracking-tight text-ruma-ink">Ruang nyaman untuk tumbuh dan beristirahat</h2>
                    <p className="mt-4 whitespace-pre-line text-sm leading-relaxed text-ruma-body">
                        {info?.deskripsi || `${nama} menghadirkan hunian yang bersih, tenang, dan praktis. Kami ingin setiap penghuni merasa betah melalui fasilitas terawat, informasi yang jelas, dan suasana yang saling menghargai.`}
                    </p>
                    {info?.kontak && <p className="mt-4 text-xs text-ruma-mute">Kontak pemilik: <span className="font-semibold text-ruma-ink">{info.kontak}</span></p>}
                </div>
            </section>

            <section className="mt-10 grid gap-6 md:grid-cols-2">
                <div>
                    <h2 className="text-xl font-extrabold tracking-tight text-ruma-ink">Peraturan kos</h2>
                    <ul className="mt-5 space-y-3.5">
                        {info?.peraturan?.length > 0 ? info.peraturan.map((aturan, i) => (
                            <li key={i} className="flex items-start gap-3 text-xs font-medium text-ruma-body">
                                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ruma-tint text-ruma-green"><Check size={12} /></span>
                                <span className="pt-1">{aturan}</span>
                            </li>
                        )) : <li className="text-xs text-ruma-mute">Peraturan kos akan segera diinformasikan oleh pengelola.</li>}
                    </ul>
                </div>

                <div className="flex flex-col justify-between rounded-2xl bg-ruma-green p-7 text-white">
                    <div>
                        <h2 className="text-xl font-extrabold tracking-tight">Yuk, temukan kamar yang cocok</h2>
                        <p className="mt-3 max-w-xs text-xs leading-relaxed text-white/80">Lihat pilihan kamar dan temukan tempat tinggal yang pas buat keseharianmu.</p>
                    </div>
                    <div className="mt-10 flex flex-wrap gap-3">
                        <Link href="/kamar" className="inline-flex items-center rounded-xl bg-white px-5 py-3 text-xs font-bold text-ruma-green transition hover:bg-ruma-tint">Lihat Kamar</Link>
                        {info?.wa && <a href={info.wa} target="_blank" rel="noopener noreferrer" className="inline-flex items-center rounded-xl border border-white/30 px-5 py-3 text-xs font-bold text-white transition hover:bg-white/10">Hubungi Pemilik</a>}
                    </div>
                </div>
            </section>

            <section className="mt-10 pb-8">
                <h2 className="text-xl font-extrabold tracking-tight text-ruma-ink">Lokasi strategis dan mudah dijangkau</h2>
                <div className="card mt-5 flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-ruma-tint text-ruma-green"><LocateFixed size={18} /></span>
                        <p className="text-xs text-ruma-body">{info?.lokasi ?? 'Alamat lengkap akan ditampilkan di bagian ini.'}</p>
                    </div>
                    {info?.maps && <a href={info.maps} target="_blank" rel="noopener noreferrer" className="btn-primary !px-5 !py-2.5 !text-xs">Lihat di Maps</a>}
                </div>
            </section>
        </div>
    );
}

Tentang.layout = (page) => <PublicLayout>{page}</PublicLayout>;
