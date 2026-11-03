import { useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Check, Clock, Printer, X } from 'lucide-react';
import PublicLayout from '../../components/public/PublicLayout';
import { cx, rupiah } from '../../lib/utils';

function statusTampil(p) {
    if (p.status === 'Terkonfirmasi') return { judul: 'Pembayaran terverifikasi', ket: 'Transaksi telah diterima dan dicatat oleh pengelola kos.', badge: 'LUNAS', banner: 'bg-ruma-tint', lingkar: 'bg-ruma-green', badgeGaya: 'bg-ruma-green text-white', Ikon: Check };
    if (p.status === 'Ditolak') return { judul: 'Pembayaran ditolak', ket: `Pembayaran ini ditolak pengelola${p.alasan ? `: ${p.alasan}` : '.'}`, badge: 'DITOLAK', banner: 'bg-rose-50', lingkar: 'bg-rose-600', badgeGaya: 'bg-rose-600 text-white', Ikon: X };
    if (p.status === 'Hangus') return { judul: 'Booking hangus', ket: 'Batas waktu 1x24 jam terlewati sehingga pembayaran dibatalkan otomatis.', badge: 'HANGUS', banner: 'bg-slate-100', lingkar: 'bg-slate-500', badgeGaya: 'bg-slate-500 text-white', Ikon: X };
    if (p.metode === 'Bayar Langsung') return { judul: 'Menunggu pembayaran di kos', ket: `Datang ke kos dan bayar langsung ke pengelola${p.batas ? ` sebelum ${p.batas}` : ' dalam 1x24 jam'}. Lewat dari itu booking hangus otomatis.`, badge: 'BELUM LUNAS', banner: 'bg-amber-50', lingkar: 'bg-amber-500', badgeGaya: 'bg-amber-500 text-white', Ikon: Clock };

    return { judul: 'Menunggu konfirmasi pengelola', ket: 'Bukti transfer sudah kami terima dan sedang diperiksa. Bukti ini berstatus LUNAS setelah terkonfirmasi.', badge: 'MENUNGGU', banner: 'bg-amber-50', lingkar: 'bg-amber-500', badgeGaya: 'bg-amber-500 text-white', Ikon: Clock };
}

export default function Receipt({ pembayaran: p, kembali, cetak }) {
    const { kos } = usePage().props;
    const nama = kos?.nama ?? 'Kos';
    const s = statusTampil(p);

    useEffect(() => {
        if (!cetak) return undefined;
        const t = setTimeout(() => window.print(), 500);
        return () => clearTimeout(t);
    }, [cetak]);

    const baris = [
        ['Tanggal bayar', p.tanggal],
        ['Kode booking', p.kode],
        ['Nama penyewa', p.nama],
        ['Metode pembayaran', p.metode],
        ['Periode sewa', p.periode],
        ['Kamar', `Kamar ${p.kamar}`],
    ];

    return (
        <div className="mx-auto max-w-3xl px-5 py-10 print:max-w-none print:px-0 print:py-0">
            <Head title="Bukti Pembayaran" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
                <Link href={kembali} className="text-xs font-semibold text-ruma-green hover:underline">&larr; Kembali ke pembayaran</Link>
                <button type="button" onClick={() => window.print()} className="btn-primary !py-2.5 !text-xs"><Printer size={14} /> Cetak Bukti Pembayaran</button>
            </div>

            <article className="card p-8 md:p-10 print:rounded-none print:border-0 print:p-0">
                <header className="flex items-start justify-between border-b border-ruma-line pb-5">
                    <div className="leading-none">
                        <p className="text-2xl font-extrabold tracking-tight text-ruma-green">{nama.toLowerCase()}.</p>
                        <p className="mt-1 text-[8px] font-semibold uppercase tracking-wider text-ruma-mute">Kos nyaman &bull; Hidup lebih tenang</p>
                    </div>
                    <div className="text-right">
                        <p className="text-[10px] font-bold uppercase tracking-wider text-ruma-ink">Bukti pembayaran resmi</p>
                        <p className="mt-1 text-[10px] text-ruma-mute">{p.dicetak}</p>
                    </div>
                </header>

                <h1 className="mt-7 text-3xl font-extrabold tracking-tight text-ruma-ink">Bukti Pembayaran</h1>
                <p className="mt-1 text-xs text-ruma-mute">Sewa kamar kos &middot; Salinan untuk penyewa</p>

                <div className={cx('mt-6 flex items-center gap-4 rounded-2xl p-5', s.banner)}>
                    <span className={cx('flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-white', s.lingkar)}><s.Ikon size={20} /></span>
                    <div className="flex-1">
                        <p className="text-base font-extrabold text-ruma-ink">{s.judul}</p>
                        <p className="mt-0.5 text-xs text-ruma-body">{s.ket}</p>
                    </div>
                    <span className={cx('rounded-full px-5 py-2 text-xs font-extrabold tracking-wider', s.badgeGaya)}>{s.badge}</span>
                </div>

                <section className="card mt-7 p-6">
                    <h2 className="text-sm font-bold text-ruma-ink">Informasi transaksi</h2>
                    <dl className="mt-4 grid gap-x-10 border-t border-ruma-line text-xs sm:grid-cols-2">
                        {baris.map(([label, nilai]) => (
                            <div key={label} className="border-b border-ruma-line py-3.5">
                                <dt className="text-[10px] text-ruma-mute">{label}</dt>
                                <dd className="mt-1 font-bold text-ruma-ink">{nilai}</dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <section className="card mt-6 p-6">
                    <h2 className="text-sm font-bold text-ruma-ink">Rincian pembayaran</h2>
                    <div className="mt-4 flex justify-between rounded-lg bg-ruma-bg px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-ruma-mute"><span>Deskripsi</span><span>Jumlah</span></div>
                    <div className="flex justify-between border-b border-ruma-line px-4 py-4 text-xs text-ruma-body">
                        <span>Sewa kamar {p.kamar} · 1 bulan</span><span>{rupiah(p.jumlah)}</span>
                    </div>
                    <div className="flex items-center justify-between px-4 pt-5">
                        <span className="text-sm font-bold text-ruma-ink">Total dibayar</span>
                        <span className="text-2xl font-extrabold text-ruma-green">{rupiah(p.jumlah)}</span>
                    </div>
                </section>

                <section className="mt-6 rounded-2xl bg-ruma-bg p-5">
                    <p className="text-xs font-bold text-ruma-ink">Catatan</p>
                    <p className="mt-1.5 text-[11px] leading-relaxed text-ruma-body">Simpan bukti ini sebagai arsip pembayaran. Jika ada pertanyaan, hubungi pengelola kos.</p>
                </section>

                <footer className="mt-10 flex items-end justify-between text-[10px] text-ruma-mute">
                    <div>
                        <p>Diterima dan dicatat oleh</p>
                        <p className="mt-8 border-t border-ruma-line pr-16 pt-2 text-xs font-bold text-ruma-ink">Pengelola {nama}</p>
                    </div>
                    <p>Kode booking {p.kode} &middot; {nama}</p>
                </footer>
            </article>
        </div>
    );
}

Receipt.layout = (page) => <PublicLayout>{page}</PublicLayout>;
