import { Link } from '@inertiajs/react';
import { rupiah } from '../../lib/utils';
import { FotoKamar, StatusKamar } from './Foto';

export default function KamarCard({ kamar }) {
    const subjudul = ['Kamar nyaman', kamar.fasilitas?.[0]?.nama].filter(Boolean).join(' • ');
    const tersedia = kamar.status === 'kosong';
    const href = `/kamar/${kamar.id}`;

    return (
        <article className="card flex flex-col p-3 transition hover:shadow-lg hover:shadow-ruma-green/5">
            <Link href={href} className="block overflow-hidden rounded-xl">
                <FotoKamar src={kamar.foto} className="h-48 w-full transition duration-500 hover:scale-105" />
            </Link>
            <div className="flex flex-1 flex-col px-2 pb-2 pt-4">
                <h3 className="text-[15px] font-bold text-ruma-ink">Kamar {kamar.nomor}</h3>
                <div className="mt-1.5 flex items-center justify-between gap-3">
                    <p className="truncate text-[11px] text-ruma-mute">{subjudul}</p>
                    <StatusKamar status={kamar.status} />
                </div>
                <p className="mt-3 text-base font-extrabold text-ruma-green">
                    {rupiah(kamar.biaya)} <span className="ml-1 text-[11px] font-medium text-ruma-mute">/ bulan</span>
                </p>
                {tersedia ? (
                    <Link href={href} className="btn-primary mt-4 w-full">Lihat Detail</Link>
                ) : (
                    <span className="btn-outline mt-4 w-full cursor-not-allowed opacity-80">
                        {kamar.status === 'reservasi' ? 'Sedang Dipesan' : 'Kamar Terisi'}
                    </span>
                )}
            </div>
        </article>
    );
}
