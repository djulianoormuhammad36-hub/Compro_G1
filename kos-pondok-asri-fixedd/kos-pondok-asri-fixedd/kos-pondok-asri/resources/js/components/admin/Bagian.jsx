import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { X } from 'lucide-react';
import { cx } from '../../lib/utils';

export function Modal({ buka, onTutup, judul, children, lebar = 'max-w-lg' }) {
    useEffect(() => {
        if (!buka) return undefined;
        const esc = (e) => e.key === 'Escape' && onTutup();
        document.addEventListener('keydown', esc);
        return () => document.removeEventListener('keydown', esc);
    }, [buka]);

    if (!buka) return null;

    return (
        <div className="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm sm:items-center" onMouseDown={(e) => e.target === e.currentTarget && onTutup()}>
            <div className={cx('my-6 w-full rounded-2xl bg-white shadow-2xl', lebar)} role="dialog" aria-modal="true" aria-label={judul}>
                <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 className="text-base font-bold text-slate-900">{judul}</h3>
                    <button onClick={onTutup} className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup"><X size={18} /></button>
                </div>
                <div className="p-6">{children}</div>
            </div>
        </div>
    );
}

export function Konfirmasi({ buka, onTutup, onYa, judul, pesan, tombol = 'Ya, lanjutkan', bahaya = false, proses = false }) {
    return (
        <Modal buka={buka} onTutup={onTutup} judul={judul} lebar="max-w-md">
            <p className="text-sm leading-relaxed text-slate-600">{pesan}</p>
            <div className="mt-6 flex justify-end gap-3">
                <button type="button" onClick={onTutup} className="a-btn-outline">Batal</button>
                <button type="button" onClick={onYa} disabled={proses} className={bahaya ? 'a-btn bg-rose-600 text-white hover:bg-rose-700' : 'a-btn-primary'}>{tombol}</button>
            </div>
        </Modal>
    );
}

export function Paginasi({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1.5 border-t border-slate-100 p-4">
            {links.map((l, i) => {
                const label = i === 0 ? '‹' : i === links.length - 1 ? '›' : l.label;
                const gaya = cx('min-w-9 rounded-lg px-3 py-2 text-center text-xs font-semibold transition',
                    l.active ? 'bg-emerald-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                    !l.url && 'pointer-events-none opacity-40');
                return l.url ? <Link key={i} href={l.url} preserveScroll className={gaya}>{label}</Link> : <span key={i} className={gaya}>{label}</span>;
            })}
        </nav>
    );
}

export function Lencana({ warna = 'slate', children, className }) {
    const peta = {
        hijau: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        kuning: 'bg-amber-100 text-amber-800 border-amber-200',
        merah: 'bg-rose-100 text-rose-800 border-rose-200',
        abu: 'bg-slate-100 text-slate-600 border-slate-200',
        slate: 'bg-slate-100 text-slate-600 border-slate-200',
    };

    return <span className={cx('inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold', peta[warna] ?? peta.slate, className)}>{children}</span>;
}

export const warnaPembayaran = (s) => ({ Terkonfirmasi: 'hijau', 'Menunggu Konfirmasi': 'kuning', Ditolak: 'merah', Hangus: 'abu' }[s] ?? 'slate');

export function Kolom({ label, error, children }) {
    return (
        <div>
            <label className="a-label">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}

const formatUkuran = (b) => (b >= 1024 * 1024 ? `${+(b / 1024 / 1024).toFixed(1)} MB` : `${Math.round(b / 1024)} KB`);

export function UnggahFoto({ label, url, file, onPilih, hapus, onHapus, error, batas = 5 * 1024 * 1024 }) {
    const [lokal, setLokal] = useState(null);
    const pratinjau = file ? URL.createObjectURL(file) : hapus ? null : url;

    const pilih = (e) => {
        const f = e.target.files?.[0] ?? null;
        if (f && f.size > batas) {
            e.target.value = '';
            setLokal(`Foto terlalu besar (${formatUkuran(f.size)}). Maksimal ${formatUkuran(batas)}.`);
            onPilih(null);
            return;
        }
        setLokal(null);
        onPilih(f);
    };

    return (
        <Kolom label={label} error={lokal || error}>
            <div className="flex items-start gap-4">
                {pratinjau
                    ? <img src={pratinjau} alt="" className="h-24 w-32 rounded-xl border border-slate-200 object-cover" />
                    : <div className="flex h-24 w-32 items-center justify-center rounded-xl border border-dashed border-slate-300 text-[11px] text-slate-400">Belum ada foto</div>}
                <div className="flex-1 space-y-2">
                    <input type="file" accept="image/png,image/jpeg,image/webp" onChange={pilih}
                        className="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                    {url && !file && (
                        <label className="flex items-center gap-2 text-xs text-slate-500">
                            <input type="checkbox" checked={hapus} onChange={(e) => onHapus(e.target.checked)} className="rounded border-slate-300 text-rose-600" /> Hapus foto ini
                        </label>
                    )}
                    <p className="text-[11px] text-slate-400">JPG, PNG, atau WEBP, maks. {formatUkuran(batas)}.</p>
                </div>
            </div>
        </Kolom>
    );
}
