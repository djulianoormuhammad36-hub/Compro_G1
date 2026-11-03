import { Head, Link, usePage } from '@inertiajs/react';
import { Wifi, SquareParking, CookingPot, Shirt, Snowflake, ShowerHead, ShieldCheck, CircleCheck } from 'lucide-react';
import PublicLayout from '../components/public/PublicLayout';
import KamarCard from '../components/public/KamarCard';
import { FotoKamar } from '../components/public/Foto';

const IKON = {
    'fa-wifi': Wifi, 'fa-square-parking': SquareParking, 'fa-kitchen-set': CookingPot, 'fa-shirt': Shirt,
    'fa-snowflake': Snowflake, 'fa-shower': ShowerHead, 'fa-shield-halved': ShieldCheck,
};

export default function Home({ kamarList, fasilitasList, hargaMulai }) {
    const { kos } = usePage().props;

    return (
        <div className="mx-auto max-w-6xl px-5">
            <Head title={kos?.nama ? `${kos.nama} - Kos yang terasa seperti rumah` : 'Beranda'} />

            <section className="grid items-center gap-10 py-12 md:grid-cols-2 md:py-16">
                <div>
                    <p className="eyebrow">Kos yang terasa seperti rumah</p>
                    <h1 className="mt-3 text-4xl font-extrabold leading-[1.15] tracking-tight text-ruma-ink md:text-[44px]">
                        Temukan ruang nyaman untuk cerita barumu.
                    </h1>
                    <p className="mt-8 max-w-md text-sm leading-relaxed text-ruma-body">
                        Pilihan kamar yang bersih, fasilitas lengkap, dan suasana yang bikin betah. Mulai cari kamar yang pas buat kamu.
                    </p>
                    <div className="mt-9 flex flex-wrap gap-3">
                        <Link href="/kamar" className="btn-primary">Temukan Kamar</Link>
                        <a href="#fasilitas" className="btn-outline">Lihat Fasilitas</a>
                    </div>
                    <span className="mt-6 inline-block rounded-full bg-ruma-tint px-4 py-1.5 text-[11px] font-semibold text-ruma-green">
                        Kamar tersedia untuk dibooking
                    </span>
                    <p className="mt-5 text-[11px] text-ruma-mute">Informasi jelas &bull; Booking praktis &bull; Kontak pemilik langsung</p>
                </div>

                <div className="relative pb-8">
                    <FotoKamar className="h-[320px] w-full rounded-2xl shadow-sm md:h-[360px]" />
                    {hargaMulai && (
                        <div className="card absolute bottom-0 right-4 w-60 p-5 shadow-lg shadow-black/5 md:right-6">
                            <p className="text-[9px] font-semibold uppercase tracking-wider text-ruma-mute">Mulai dari</p>
                            <p className="mt-1 text-xl font-extrabold text-ruma-green">
                                {hargaMulai} <span className="text-[11px] font-medium text-ruma-mute">/ bulan</span>
                            </p>
                            <p className="mt-1 text-[11px] text-ruma-mute">Cek kamar tersedia hari ini</p>
                        </div>
                    )}
                </div>
            </section>

            {fasilitasList.length > 0 && (
                <section id="fasilitas" className="scroll-mt-24 py-8">
                    <h2 className="text-2xl font-extrabold tracking-tight text-ruma-ink">Semua yang kamu butuhkan untuk tinggal nyaman</h2>
                    <p className="mt-2 text-xs text-ruma-mute">Temukan fasilitas penunjang aktivitas sehari-hari.</p>
                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {fasilitasList.map((f) => {
                            const Ikon = IKON[f.ikon] ?? CircleCheck;
                            return (
                                <div key={f.id} className="card flex items-center gap-4 p-4">
                                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ruma-tint text-ruma-green"><Ikon size={18} /></span>
                                    <div className="min-w-0">
                                        <h3 className="text-[13px] font-bold text-ruma-ink">{f.nama}</h3>
                                        <p className="mt-0.5 line-clamp-2 text-[11px] text-ruma-mute">{f.deskripsi}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            <section className="py-10 pb-16">
                <div className="flex items-end justify-between">
                    <h2 className="text-2xl font-extrabold tracking-tight text-ruma-ink">Pilih kamar favoritmu</h2>
                    <Link href="/kamar" className="text-xs font-semibold text-ruma-green hover:underline">Lihat semua kamar &rarr;</Link>
                </div>
                <div className="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {kamarList.length > 0
                        ? kamarList.map((k) => <KamarCard key={k.id} kamar={k} />)
                        : <p className="card col-span-full p-10 text-center text-sm text-ruma-mute">Belum ada kamar yang ditampilkan.</p>}
                </div>
            </section>
        </div>
    );
}

Home.layout = (page) => <PublicLayout>{page}</PublicLayout>;
