import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import PublicLayout from '../../components/public/PublicLayout';
import KamarCard from '../../components/public/KamarCard';
import { Breadcrumb, Paginasi } from '../../components/public/Bagian';

export default function Index({ kamars, filters }) {
    const [search, setSearch] = useState(filters.search);
    const awal = useRef(true);

    const terapkan = (ubah) => {
        const data = { search, status: filters.status, sort: filters.sort, ...ubah };
        router.get('/kamar', {
            ...(data.search ? { search: data.search } : {}),
            ...(data.status && data.status !== 'all' ? { status: data.status } : {}),
            ...(data.sort ? { sort: data.sort } : {}),
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    // pencarian nomor kamar otomatis setelah berhenti mengetik
    useEffect(() => {
        if (awal.current) { awal.current = false; return undefined; }
        const t = setTimeout(() => terapkan({ search }), 400);
        return () => clearTimeout(t);
    }, [search]);

    return (
        <div className="mx-auto max-w-6xl px-5 py-10">
            <Head title="Daftar Kamar" />
            <Breadcrumb items={[['Beranda', '/'], ['Kamar']]} />

            <h1 className="mt-6 text-3xl font-extrabold tracking-tight text-ruma-ink md:text-4xl">Kamar untuk kebutuhanmu</h1>
            <p className="mt-2 text-sm text-ruma-mute">Bandingkan harga, status, dan detail kamar sebelum booking.</p>

            <div className="card mt-8 grid items-end gap-4 p-4 md:grid-cols-[1fr_220px_220px]">
                <label className="flex items-center gap-3">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ruma-tint text-ruma-green"><Search size={16} /></span>
                    <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor kamar..."
                        className="w-full border-0 bg-transparent text-sm text-ruma-ink placeholder:text-ruma-mute focus:outline-none focus:ring-0" />
                </label>
                <div>
                    <label className="mb-1 block text-[10px] font-semibold text-ruma-mute">Status kamar</label>
                    <select value={filters.status} onChange={(e) => terapkan({ status: e.target.value })} className="field !rounded-lg !bg-ruma-bg !py-2 text-xs">
                        <option value="all">Semua status</option>
                        <option value="kosong">Tersedia</option>
                        <option value="reservasi">Dipesan</option>
                        <option value="terisi">Terisi</option>
                    </select>
                </div>
                <div>
                    <label className="mb-1 block text-[10px] font-semibold text-ruma-mute">Harga sewa</label>
                    <select value={filters.sort} onChange={(e) => terapkan({ sort: e.target.value })} className="field !rounded-lg !bg-ruma-bg !py-2 text-xs">
                        <option value="">Nomor kamar</option>
                        <option value="lowest">Terendah</option>
                        <option value="highest">Tertinggi</option>
                    </select>
                </div>
            </div>

            <p className="mt-6 text-[11px] font-semibold text-ruma-ink">{kamars.total} kamar ditemukan</p>

            <div className="mt-4 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {kamars.data.length > 0
                    ? kamars.data.map((k) => <KamarCard key={k.id} kamar={k} />)
                    : <p className="card col-span-full p-12 text-center text-sm text-ruma-mute">Tidak ada kamar yang cocok dengan pencarian Anda.</p>}
            </div>

            <div className="mt-10"><Paginasi links={kamars.links} /></div>
        </div>
    );
}

Index.layout = (page) => <PublicLayout>{page}</PublicLayout>;
