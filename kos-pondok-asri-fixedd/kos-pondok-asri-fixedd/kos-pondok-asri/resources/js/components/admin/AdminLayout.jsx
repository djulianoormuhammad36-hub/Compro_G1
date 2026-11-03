import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, AlertTriangle, XCircle, LayoutDashboard, DoorOpen, ListChecks, Users, ReceiptText, Store, Globe, LogOut, Menu, X, House, KeyRound, MessageCircle } from 'lucide-react';
import { cx } from '../../lib/utils';

const MENU = [
    { label: 'Dashboard', href: '/admin/dashboard', ikon: LayoutDashboard },
    { label: 'Kelola Kamar', href: '/admin/kamar', ikon: DoorOpen },
    { label: 'Fasilitas', href: '/admin/fasilitas', ikon: ListChecks },
    { label: 'Penghuni', href: '/admin/penghuni', ikon: Users },
    { label: 'Konfirmasi Pembayaran', href: '/admin/konfirmasi', ikon: ReceiptText, lencana: true },
    { label: 'Info Kos', href: '/admin/info-kos', ikon: Store },
    { label: 'Akun Admin', href: '/admin/akun', ikon: KeyRound },
];

function Toast({ flash }) {
    const [tampil, setTampil] = useState(null);
    const kunci = JSON.stringify(flash ?? {});

    useEffect(() => {
        const pesan = flash?.error ? ['error', flash.error] : flash?.warning ? ['warning', flash.warning] : flash?.success ? ['success', flash.success] : null;
        setTampil(pesan);
        if (!pesan) return undefined;
        const t = setTimeout(() => setTampil(null), 6000);
        return () => clearTimeout(t);
    }, [kunci]);

    if (!tampil) return null;
    const [jenis, teks] = tampil;
    const [gaya, Ikon] = {
        success: ['border-emerald-200 bg-emerald-50 text-emerald-800', CheckCircle2],
        warning: ['border-amber-200 bg-amber-50 text-amber-800', AlertTriangle],
        error: ['border-rose-200 bg-rose-50 text-rose-700', XCircle],
    }[jenis];

    return (
        <div role="status" className={cx('fixed bottom-5 right-5 z-[70] flex max-w-sm items-start gap-3 rounded-2xl border p-4 text-sm font-medium shadow-lg', gaya)}>
            <Ikon size={18} className="mt-0.5 shrink-0" />
            <p className="flex-1">{teks}</p>
            <button onClick={() => setTampil(null)} className="opacity-60 hover:opacity-100" aria-label="Tutup"><X size={16} /></button>
        </div>
    );
}

const KABAR = {
    disetujui: ['Pembayaran disetujui ✔', 'Kabari {nama} lewat WhatsApp agar tahu pembayarannya sudah diterima.'],
    ditolak: ['Pembayaran ditolak', 'Kabari {nama} lewat WhatsApp beserta alasan penolakannya.'],
    hangus: ['Booking hangus', 'Kabari {nama} lewat WhatsApp bahwa bookingnya dibatalkan.'],
};

/** Muncul setelah admin menyetujui/menolak: tombol membuka WhatsApp dengan pesan yang sudah terisi. */
function KabarWhatsApp({ wa }) {
    const [tutup, setTutup] = useState(false);
    const kunci = wa ? `${wa.url}` : '';

    useEffect(() => setTutup(false), [kunci]);

    if (!wa || tutup) return null;
    const [judul, isi] = KABAR[wa.jenis] ?? KABAR.disetujui;

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><MessageCircle size={22} /></span>
                <h3 className="mt-4 text-lg font-extrabold text-slate-900">{judul}</h3>
                <p className="mt-1 text-sm leading-relaxed text-slate-500">{isi.replace('{nama}', wa.nama)}</p>
                <p className="mt-2 text-xs text-slate-400">Pesan sudah terisi otomatis; Anda tinggal menekan Kirim di WhatsApp.</p>
                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button onClick={() => setTutup(true)} className="a-btn-outline">Nanti saja</button>
                    <a href={wa.url} target="_blank" rel="noopener noreferrer" onClick={() => setTutup(true)} className="a-btn-primary">
                        <MessageCircle size={16} /> Buka WhatsApp
                    </a>
                </div>
            </div>
        </div>
    );
}

export default function AdminLayout({ title, children }) {
    const { props, url } = usePage();
    const { auth, kos, pending, flash } = props;
    const [buka, setBuka] = useState(false);
    const path = url.split('?')[0];

    return (
        <div className="min-h-screen bg-slate-100 font-admin text-slate-800">
            {buka && <div className="fixed inset-0 z-40 bg-slate-900/80 lg:hidden" onClick={() => setBuka(false)} />}

            <aside className={cx('fixed inset-y-0 left-0 z-50 flex w-72 flex-col justify-between bg-slate-900 text-white shadow-2xl transition-transform duration-300 lg:translate-x-0',
                buka ? 'translate-x-0' : '-translate-x-full')}>
                <div>
                    <div className="flex h-20 items-center justify-between border-b border-slate-800 bg-slate-950/60 px-6">
                        <Link href="/admin/dashboard" className="flex items-center gap-3">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30"><House size={20} /></span>
                            <span className="flex flex-col">
                                <span className="text-lg font-bold leading-none tracking-tight text-white">{kos?.nama ?? 'Admin'}</span>
                                <span className="mt-1 text-[10px] font-medium uppercase tracking-widest text-emerald-400">Admin Panel</span>
                            </span>
                        </Link>
                        <button onClick={() => setBuka(false)} className="text-slate-400 hover:text-white lg:hidden"><X size={20} /></button>
                    </div>

                    <nav className="space-y-1.5 px-4 py-6">
                        <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu Utama</p>
                        {MENU.map(({ label, href, ikon: Ikon, lencana }) => {
                            const aktif = path === href || path.startsWith(href + '/');
                            return (
                                <Link key={href} href={href} onClick={() => setBuka(false)}
                                    className={cx('flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all',
                                        aktif ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'text-slate-300 hover:bg-slate-800 hover:text-white')}>
                                    <Ikon size={18} className="w-5" />
                                    <span className="flex-1">{label}</span>
                                    {lencana && pending > 0 && <span className="rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-slate-900">{pending}</span>}
                                </Link>
                            );
                        })}

                        <p className="px-3 pb-2 pt-6 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Navigasi Sistem</p>
                        <a href="/" target="_blank" rel="noopener noreferrer" className="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-300 transition-all hover:bg-slate-800 hover:text-white">
                            <Globe size={18} className="w-5" /><span>Lihat Website Utama</span>
                        </a>
                    </nav>
                </div>

                <div className="border-t border-slate-800 p-4">
                    <div className="flex items-center justify-between rounded-xl bg-slate-800/60 p-3">
                        <div className="flex min-w-0 flex-col">
                            <span className="truncate text-sm font-semibold text-white">{auth.user?.name}</span>
                            <span className="truncate text-xs text-slate-400">{auth.user?.email}</span>
                        </div>
                        <button onClick={() => router.post('/logout')} className="p-2 text-slate-400 hover:text-rose-400" title="Keluar"><LogOut size={18} /></button>
                    </div>
                </div>
            </aside>

            <div className="lg:pl-72">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
                    <button onClick={() => setBuka(true)} className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"><Menu size={20} /></button>
                    <h1 className="text-lg font-bold text-slate-900">{title}</h1>
                </header>
                <main className="p-4 sm:p-8">{children}</main>
            </div>

            <Toast flash={flash} />
            <KabarWhatsApp wa={flash?.wa} />
        </div>
    );
}