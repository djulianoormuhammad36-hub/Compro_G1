import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Menu, X, CheckCircle2, AlertTriangle, XCircle } from 'lucide-react';
import { cx } from '../../lib/utils';

function Wordmark({ nama, kecil = false }) {
    return (
        <span className="flex flex-col leading-none">
            <span className={cx('font-extrabold tracking-tight text-ruma-green', kecil ? 'text-lg' : 'text-[22px]')}>
                {(nama || 'Kos').toLowerCase()}.
            </span>
            <span className="mt-1 text-[8px] font-semibold uppercase tracking-wider text-ruma-mute">
                Kos nyaman &bull; Hidup lebih tenang
            </span>
        </span>
    );
}

function Navbar({ kos, url }) {
    const [buka, setBuka] = useState(false);
    const path = url.split('?')[0];
    const menu = [
        ['Beranda', '/', path === '/'],
        ['Kamar', '/kamar', path.startsWith('/kamar') || path.startsWith('/booking') || path.startsWith('/pembayaran')],
        ['Tentang Kos', '/tentang', path === '/tentang'],
    ];
    const hubungi = kos?.wa ?? '/tentang';
    const eksternal = kos?.wa ? { target: '_blank', rel: 'noopener noreferrer' } : {};

    return (
        <nav className="sticky top-0 z-50 border-b border-ruma-line bg-white print:hidden">
            <div className="mx-auto flex h-[68px] max-w-6xl items-center justify-between px-5">
                <Link href="/"><Wordmark nama={kos?.nama} /></Link>

                <div className="hidden items-center gap-12 md:flex">
                    {menu.map(([label, href, on]) => (
                        <Link key={href} href={href} className={cx('text-[13px] transition', on ? 'font-bold text-ruma-green' : 'font-medium text-ruma-body hover:text-ruma-green')}>
                            {label}
                        </Link>
                    ))}
                </div>

                <div className="hidden items-center gap-3 md:flex">
                    <a href={hubungi} {...eksternal} className="btn-outline !px-4 !py-2.5 !text-xs">Hubungi Kami</a>
                    <Link href="/kamar" className="btn-primary !px-4 !py-2.5 !text-xs">Booking Kamar</Link>
                </div>

                <button onClick={() => setBuka(!buka)} className="rounded-lg p-2 text-ruma-green md:hidden" aria-label="Menu">
                    {buka ? <X size={20} /> : <Menu size={20} />}
                </button>
            </div>

            {buka && (
                <div className="border-t border-ruma-line bg-white px-5 py-4 md:hidden">
                    <div className="flex flex-col gap-3">
                        {menu.map(([label, href, on]) => (
                            <Link key={href} href={href} onClick={() => setBuka(false)} className={cx('text-sm', on ? 'font-bold text-ruma-green' : 'font-medium text-ruma-body')}>{label}</Link>
                        ))}
                        <div className="mt-2 flex gap-3">
                            <a href={hubungi} {...eksternal} className="btn-outline flex-1 !py-2.5 !text-xs">Hubungi Kami</a>
                            <Link href="/kamar" className="btn-primary flex-1 !py-2.5 !text-xs">Booking Kamar</Link>
                        </div>
                    </div>
                </div>
            )}
        </nav>
    );
}

function Footer({ kos, auth }) {
    return (
        <footer className="mt-auto border-t border-ruma-line bg-white print:hidden">
            <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-5 py-8 text-xs text-ruma-mute sm:flex-row">
                <Wordmark nama={kos?.nama} kecil />
                <p>&copy; {new Date().getFullYear()} {kos?.nama ?? 'Kos'}. Semua hak dilindungi.</p>
                <div className="flex items-center gap-5 font-medium">
                    <Link href="/kamar" className="hover:text-ruma-green">Kamar</Link>
                    <Link href="/tentang" className="hover:text-ruma-green">Tentang Kos</Link>
                    {auth?.user ? (
                        <>
                            <Link href="/admin/dashboard" className="font-semibold text-ruma-green hover:underline">Dashboard Admin</Link>
                            <button type="button" onClick={() => router.post('/logout')} className="hover:text-rose-600">Keluar</button>
                        </>
                    ) : (
                        <Link href="/login" className="hover:text-ruma-green">Login Admin</Link>
                    )}
                </div>
            </div>
        </footer>
    );
}

function Toast({ flash }) {
    const [tampil, setTampil] = useState(null);
    const kunci = JSON.stringify(flash ?? {});

    useEffect(() => {
        const pesan = flash?.error ? ['error', flash.error] : flash?.warning ? ['warning', flash.warning] : flash?.success ? ['success', flash.success] : null;
        setTampil(pesan);
        if (!pesan) return undefined;
        const t = setTimeout(() => setTampil(null), 7000);
        return () => clearTimeout(t);
    }, [kunci]);

    if (!tampil) return null;
    const [jenis, teks] = tampil;
    const [gaya, Ikon] = {
        success: ['border-ruma-green/20 bg-ruma-tint text-ruma-green', CheckCircle2],
        warning: ['border-amber-200 bg-amber-50 text-amber-800', AlertTriangle],
        error: ['border-rose-200 bg-rose-50 text-rose-700', XCircle],
    }[jenis];

    return (
        <div role="status" className={cx('fixed bottom-5 right-5 z-[60] flex max-w-sm items-start gap-3 rounded-2xl border p-4 text-sm font-medium shadow-lg print:hidden', gaya)}>
            <Ikon size={18} className="mt-0.5 shrink-0" />
            <p className="flex-1">{teks}</p>
            <button onClick={() => setTampil(null)} className="opacity-60 hover:opacity-100" aria-label="Tutup"><X size={16} /></button>
        </div>
    );
}

export default function PublicLayout({ children }) {
    const { props, url } = usePage();
    const { kos, flash, auth } = props;

    return (
        <div className="flex min-h-screen flex-col">
            <Navbar kos={kos} url={url} />
            <main className="flex-1">{children}</main>
            <Footer kos={kos} auth={auth} />
            <Toast flash={flash} />
        </div>
    );
}