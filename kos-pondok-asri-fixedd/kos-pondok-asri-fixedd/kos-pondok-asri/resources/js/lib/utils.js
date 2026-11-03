export const cx = (...kelas) => kelas.filter(Boolean).join(' ');

export const rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n ?? 0));

export const FOTO_BAWAAN = '/images/kamar-default.svg';

export const STATUS_KAMAR = {
    kosong: { label: 'Tersedia', publik: 'bg-ruma-tint text-ruma-green', admin: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
    reservasi: { label: 'Dipesan', publik: 'bg-amber-50 text-amber-700', admin: 'bg-amber-100 text-amber-800 border-amber-200' },
    terisi: { label: 'Terisi', publik: 'bg-[#f1f3ec] text-ruma-mute', admin: 'bg-slate-200 text-slate-700 border-slate-300' },
};
