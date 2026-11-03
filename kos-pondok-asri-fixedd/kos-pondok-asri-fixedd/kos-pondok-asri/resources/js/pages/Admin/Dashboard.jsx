import { Head, Link } from '@inertiajs/react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Banknote, Bell, DoorOpen, FileDown, ReceiptText, Users, ArrowRight } from 'lucide-react';
import AdminLayout from '../../components/admin/AdminLayout';
import { Lencana, warnaPembayaran } from '../../components/admin/Bagian';
import { rupiah } from '../../lib/utils';

const WARNA_KAMAR = { kosong: '#10b981', reservasi: '#f59e0b', terisi: '#64748b' };
const WARNA_METODE = ['#059669', '#0ea5e9', '#f59e0b', '#a855f7'];
const ringkas = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 });

function Kartu({ judul, nilai, ikon: Ikon, warna, tautan }) {
    const isi = (
        <div className="a-card flex items-center gap-4 p-5 transition hover:shadow-md">
            <span className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${warna}`}><Ikon size={20} /></span>
            <div className="min-w-0">
                <span className="block text-xs font-semibold uppercase tracking-wider text-slate-400">{judul}</span>
                <span className="block truncate text-2xl font-extrabold text-slate-900">{nilai}</span>
            </div>
        </div>
    );

    return tautan ? <Link href={tautan}>{isi}</Link> : isi;
}

function Panel({ judul, sub, children, className = '' }) {
    return (
        <section className={`a-card p-6 ${className}`}>
            <h2 className="text-base font-bold text-slate-900">{judul}</h2>
            {sub && <p className="text-xs text-slate-400">{sub}</p>}
            <div className="mt-4">{children}</div>
        </section>
    );
}

function Kosong({ teks }) {
    return <div className="flex h-56 items-center justify-center text-sm text-slate-400">{teks}</div>;
}

export default function Dashboard({ stat, tahun, pemasukanBulanan, komposisiKamar, bookingHarian, metodePembayaran, aktivitas }) {
    const adaPemasukan = pemasukanBulanan.some((b) => b.total > 0);
    const adaBooking = bookingHarian.some((b) => b.jumlah > 0);
    const totalKamar = komposisiKamar.reduce((a, b) => a + b.value, 0);

    return (
        <div className="space-y-6">
            <Head title="Dashboard Admin" />

            {stat.menungguKonfirmasi > 0 && (
                <Link href="/admin/konfirmasi" className="flex items-center justify-between rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 transition hover:bg-amber-100">
                    <span className="flex items-center gap-2 text-sm font-semibold"><Bell size={16} />{stat.menungguKonfirmasi} pembayaran menunggu konfirmasi Anda</span>
                    <ArrowRight size={16} />
                </Link>
            )}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Kartu judul="Total Kamar" nilai={stat.totalKamar} ikon={DoorOpen} warna="bg-slate-100 text-slate-600" tautan="/admin/kamar" />
                <Kartu judul="Penghuni Aktif" nilai={stat.penghuniAktif} ikon={Users} warna="bg-emerald-100 text-emerald-600" tautan="/admin/penghuni" />
                <Kartu judul="Menunggu Konfirmasi" nilai={stat.menungguKonfirmasi} ikon={ReceiptText} warna="bg-amber-100 text-amber-600" tautan="/admin/konfirmasi" />
                <Kartu judul="Pemasukan Bulan Ini" nilai={rupiah(stat.pemasukanBulanIni)} ikon={Banknote} warna="bg-sky-100 text-sky-600" />
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Panel judul={`Pemasukan ${tahun}`} sub={`Total tahun ini ${rupiah(stat.pemasukanTahunIni)} (hanya pembayaran terkonfirmasi)`} className="lg:col-span-2">
                    {adaPemasukan ? (
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={pemasukanBulanan} margin={{ top: 5, right: 5, left: -10, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                    <XAxis dataKey="bulan" tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: '#94a3b8' }} />
                                    <YAxis tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: '#94a3b8' }} tickFormatter={(v) => ringkas.format(v)} />
                                    <Tooltip formatter={(v) => [rupiah(v), 'Pemasukan']} cursor={{ fill: '#f1f5f9' }} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                                    <Bar dataKey="total" fill="#10b981" radius={[6, 6, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    ) : <Kosong teks="Belum ada pemasukan terkonfirmasi tahun ini." />}
                </Panel>

                <Panel judul="Komposisi Kamar" sub={`${totalKamar} kamar terdaftar`}>
                    {totalKamar > 0 ? (
                        <>
                            <div className="h-44">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={komposisiKamar} dataKey="value" nameKey="name" innerRadius={48} outerRadius={72} paddingAngle={3} stroke="none">
                                            {komposisiKamar.map((k) => <Cell key={k.key} fill={WARNA_KAMAR[k.key]} />)}
                                        </Pie>
                                        <Tooltip formatter={(v, n) => [`${v} kamar`, n]} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <ul className="mt-2 space-y-2 text-sm">
                                {komposisiKamar.map((k) => (
                                    <li key={k.key} className="flex items-center justify-between">
                                        <span className="flex items-center gap-2 text-slate-600"><span className="h-2.5 w-2.5 rounded-full" style={{ background: WARNA_KAMAR[k.key] }} />{k.name}</span>
                                        <span className="font-bold text-slate-900">{k.value}</span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    ) : <Kosong teks="Belum ada kamar." />}
                </Panel>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Panel judul="Booking Masuk" sub="14 hari terakhir" className="lg:col-span-2">
                    {adaBooking ? (
                        <div className="h-56">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={bookingHarian} margin={{ top: 5, right: 5, left: -25, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="gBooking" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#0ea5e9" stopOpacity={0.35} /><stop offset="100%" stopColor="#0ea5e9" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                    <XAxis dataKey="hari" tickLine={false} axisLine={false} tick={{ fontSize: 10, fill: '#94a3b8' }} interval={1} />
                                    <YAxis allowDecimals={false} tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: '#94a3b8' }} />
                                    <Tooltip formatter={(v) => [`${v} booking`, 'Jumlah']} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                                    <Area type="monotone" dataKey="jumlah" stroke="#0ea5e9" strokeWidth={2.5} fill="url(#gBooking)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    ) : <Kosong teks="Belum ada booking dalam 14 hari terakhir." />}
                </Panel>

                <Panel judul="Metode Pembayaran" sub="Dari pembayaran terkonfirmasi">
                    {metodePembayaran.length > 0 ? (
                        <>
                            <div className="h-44">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie data={metodePembayaran} dataKey="value" nameKey="name" outerRadius={72} stroke="#fff" strokeWidth={2}>
                                            {metodePembayaran.map((m, i) => <Cell key={m.name} fill={WARNA_METODE[i % WARNA_METODE.length]} />)}
                                        </Pie>
                                        <Tooltip formatter={(v, n) => [`${v} transaksi`, n]} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <ul className="mt-2 space-y-2 text-sm">
                                {metodePembayaran.map((m, i) => (
                                    <li key={m.name} className="flex items-center justify-between">
                                        <span className="flex items-center gap-2 text-slate-600"><span className="h-2.5 w-2.5 rounded-full" style={{ background: WARNA_METODE[i % WARNA_METODE.length] }} />{m.name}</span>
                                        <span className="font-bold text-slate-900">{m.value}</span>
                                    </li>
                                ))}
                            </ul>
                        </>
                    ) : <Kosong teks="Belum ada pembayaran terkonfirmasi." />}
                </Panel>
            </div>

            <Panel judul="Aktivitas Terbaru" sub="Pembayaran yang baru masuk">
                <ul className="divide-y divide-slate-100">
                    {aktivitas.length > 0 ? aktivitas.map((a) => (
                        <li key={a.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p className="text-sm font-semibold text-slate-800">{a.nama} <span className="font-normal text-slate-400">· Kamar {a.kamar}</span></p>
                                <p className="text-xs text-slate-400">{a.metode} · {a.waktu}</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <Lencana warna={warnaPembayaran(a.status)}>{a.status}</Lencana>
                                <Link href={`/admin/konfirmasi/${a.id}`} className="text-xs font-semibold text-emerald-600 hover:underline">Detail</Link>
                            </div>
                        </li>
                    )) : <li className="py-6 text-center text-sm text-slate-400">Belum ada aktivitas.</li>}
                </ul>
                <a href="/admin/dashboard/export" className="a-btn-outline mt-4"><FileDown size={15} /> Unduh Laporan (CSV)</a>
            </Panel>
        </div>
    );
}

Dashboard.layout = (page) => <AdminLayout title="Dashboard">{page}</AdminLayout>;
