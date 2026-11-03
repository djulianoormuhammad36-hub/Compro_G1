import { usePage } from '@inertiajs/react';
import { FOTO_BAWAAN, STATUS_KAMAR, cx } from '../../lib/utils';

/** Foto kamar -> foto kos (diatur admin) -> ilustrasi bawaan. */
export function FotoKamar({ src, className, alt = 'Foto kamar kos' }) {
    const { kos } = usePage().props;

    return <img src={src || kos?.foto || FOTO_BAWAAN} alt={alt} loading="lazy" className={cx('object-cover', className)} />;
}

export function StatusKamar({ status, className }) {
    const s = STATUS_KAMAR[status] ?? STATUS_KAMAR.terisi;

    return (
        <span className={cx('inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold', s.publik, className)}>
            {s.label}
        </span>
    );
}
