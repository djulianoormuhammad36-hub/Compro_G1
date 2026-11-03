import { Head, Link, useForm } from '@inertiajs/react';
import { Lock, ShieldCheck } from 'lucide-react';
import PublicLayout from '../../components/public/PublicLayout';

function Isian({ label, name, form, ...props }) {
    return (
        <div>
            <label className="label" htmlFor={name}>{label}</label>
            <input id={name} value={form.data[name]} onChange={(e) => form.setData(name, e.target.value)} required className={'field ' + (form.errors[name] ? '!border-rose-400' : '')} {...props} />
            {form.errors[name] && <p className="mt-1 text-xs text-rose-600">{form.errors[name]}</p>}
        </div>
    );
}

function Ditutup({ lokal }) {
    return (
        <div className="card p-8">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600"><Lock size={22} /></span>
            <h1 className="mt-4 text-2xl font-extrabold tracking-tight text-ruma-ink">Pendaftaran akun ditutup</h1>
            <p className="mt-2 text-sm leading-relaxed text-ruma-body">
                Akun admin bisa melihat data KTP penghuni, jadi pendaftaran tidak dibuka untuk umum. Akun admin baru dibuat oleh admin yang sudah login lewat menu <b>Akun Admin</b>.
            </p>

            {lokal && (
                <div className="mt-5 rounded-xl bg-ruma-bg p-4 text-xs leading-relaxed text-ruma-body">
                    <p className="font-bold text-ruma-ink">Ingin membuat akun lewat halaman ini?</p>
                    <ol className="mt-2 list-decimal space-y-1 pl-4">
                        <li>Buka file <code className="rounded bg-white px-1">.env</code> di folder proyek.</li>
                        <li>Tambahkan baris <code className="rounded bg-white px-1">ADMIN_SETUP_KEY=kode-rahasia-anda</code> (pilih kode sendiri).</li>
                        <li>Simpan, lalu jalankan <code className="rounded bg-white px-1">php artisan config:clear</code> di terminal.</li>
                        <li>Muat ulang halaman ini. Formulir pendaftaran akan muncul dan meminta kode tersebut.</li>
                    </ol>
                </div>
            )}

            <Link href="/login" className="btn-primary mt-6 w-full">Ke halaman login</Link>
        </div>
    );
}

function Formulir({ pertama, butuhKunci }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '', kunci: '' });

    const kirim = (e) => {
        e.preventDefault();
        form.post('/daftar', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <div className="card p-8">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-ruma-tint text-ruma-green"><ShieldCheck size={22} /></span>
            <p className="eyebrow mt-4">{pertama ? 'Pengaturan awal' : 'Akun baru'}</p>
            <h1 className="mt-1 text-2xl font-extrabold tracking-tight text-ruma-ink">{pertama ? 'Buat akun admin pertama' : 'Buat akun admin'}</h1>
            <p className="mt-1 text-xs leading-relaxed text-ruma-mute">
                {pertama
                    ? 'Belum ada akun admin. Isi data di bawah, lalu Anda langsung masuk ke panel.'
                    : 'Isi data akun baru dan kode pendaftaran dari pemilik kos. Setelah berhasil Anda langsung masuk ke panel.'}
            </p>

            <form onSubmit={kirim} className="mt-6 space-y-4">
                <Isian label="Nama" name="name" form={form} autoFocus autoComplete="name" />
                <Isian label="Email (untuk login)" name="email" form={form} type="email" autoComplete="username" />
                <Isian label="Password (min. 8 karakter)" name="password" form={form} type="password" autoComplete="new-password" />
                <Isian label="Ulangi password" name="password_confirmation" form={form} type="password" autoComplete="new-password" />
                {butuhKunci && <Isian label="Kode pendaftaran" name="kunci" form={form} type="password" placeholder="Nilai ADMIN_SETUP_KEY di file .env" />}
                <button type="submit" disabled={form.processing} className="btn-primary w-full">{form.processing ? 'Membuat akun...' : 'Buat Akun & Masuk'}</button>
            </form>

            {!pertama && <p className="mt-5 text-center text-xs text-ruma-mute">Sudah punya akun? <Link href="/login" className="font-semibold text-ruma-green hover:underline">Masuk</Link></p>}
        </div>
    );
}

export default function Setup({ terbuka, pertama, butuhKunci, lokal }) {
    return (
        <div className="mx-auto max-w-md px-5 py-16">
            <Head title="Buat Akun Admin" />
            {terbuka ? <Formulir pertama={pertama} butuhKunci={butuhKunci} /> : <Ditutup lokal={lokal} />}
        </div>
    );
}

Setup.layout = (page) => <PublicLayout>{page}</PublicLayout>;