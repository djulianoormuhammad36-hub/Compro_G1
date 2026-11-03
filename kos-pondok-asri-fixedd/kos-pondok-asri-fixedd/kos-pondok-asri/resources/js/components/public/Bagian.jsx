import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { cx } from '../../lib/utils';

export function Breadcrumb({ items }) {
    return (
        <nav className="text-[10px] font-semibold uppercase tracking-wider text-ruma-mute print:hidden">
            {items.map(([label, href], i) => (
                <span key={label}>
                    {i > 0 && <span className="mx-1">/</span>}
                    {href ? <Link href={href} className="hover:text-ruma-green">{label}</Link> : <span>{label}</span>}
                </span>
            ))}
        </nav>
    );
}

export function Steps({ current }) {
    const langkah = ['Data diri', 'Review', 'Pembayaran'];

    return (
        <ol className="mt-8 flex items-center gap-4 text-xs">
            {langkah.map((nama, i) => {
                const no = i + 1;
                return (
                    <li key={nama} className="flex items-center gap-3">
                        <span className={cx('flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-bold',
                            no === current ? 'bg-ruma-green text-white' : no < current ? 'bg-ruma-tint text-ruma-green' : 'bg-ruma-tint text-ruma-mute')}>
                            {no < current ? <Check size={12} /> : String(no).padStart(2, '0')}
                        </span>
                        <span className={no === current ? 'font-semibold text-ruma-ink' : 'text-ruma-mute'}>{nama}</span>
                        {no < langkah.length && <span className="hidden h-px w-14 bg-ruma-line sm:block lg:w-28" />}
                    </li>
                );
            })}
        </ol>
    );
}

export function Paginasi({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1.5">
            {links.map((l, i) => {
                const label = i === 0 ? '‹' : i === links.length - 1 ? '›' : l.label;
                const gaya = cx('min-w-9 rounded-lg px-3 py-2 text-center text-xs font-semibold transition',
                    l.active ? 'bg-ruma-green text-white' : 'border border-ruma-line bg-white text-ruma-body hover:bg-ruma-tint',
                    !l.url && 'pointer-events-none opacity-40');
                return l.url
                    ? <Link key={i} href={l.url} preserveScroll className={gaya}>{label}</Link>
                    : <span key={i} className={gaya}>{label}</span>;
            })}
        </nav>
    );
}

const hitung = (iso) => (iso ? Math.floor((new Date(iso).getTime() - Date.now()) / 1000) : 0);

/** Hitung mundur menuju batas waktu (ISO 8601). */
export function Countdown({ iso, className }) {
    const [sisa, setSisa] = useState(() => hitung(iso));

    useEffect(() => {
        setSisa(hitung(iso));
        const t = setInterval(() => setSisa(hitung(iso)), 1000);
        return () => clearInterval(t);
    }, [iso]);

    if (!iso) return null;
    if (sisa <= 0) return <span className={className}>waktu habis</span>;

    const j = Math.floor(sisa / 3600);
    const m = Math.floor((sisa % 3600) / 60);
    const d = sisa % 60;

    return <span className={className}>{j} jam {String(m).padStart(2, '0')} menit {String(d).padStart(2, '0')} detik</span>;
}
