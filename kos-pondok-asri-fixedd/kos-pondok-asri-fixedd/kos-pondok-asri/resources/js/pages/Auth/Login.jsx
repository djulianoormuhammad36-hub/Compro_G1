import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../components/public/PublicLayout';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    const kirim = (e) => {
        e.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <div className="mx-auto max-w-md px-5 py-16">
            <Head title="Login Admin" />
            <div className="card p-8">
                <p className="eyebrow">Khusus pengelola</p>
                <h1 className="mt-2 text-2xl font-extrabold tracking-tight text-ruma-ink">Login Admin</h1>
                <p className="mt-1 text-xs text-ruma-mute">Masuk untuk mengelola kamar, penghuni, dan konfirmasi pembayaran.</p>

                <form onSubmit={kirim} className="mt-6 space-y-4">
                    <div>
                        <label className="label" htmlFor="email">Email</label>
                        <input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required autoFocus autoComplete="username"
                            className={'field ' + (form.errors.email ? '!border-rose-400' : '')} />
                        {form.errors.email && <p className="mt-1 text-xs text-rose-600">{form.errors.email}</p>}
                    </div>
                    <div>
                        <label className="label" htmlFor="password">Password</label>
                        <input id="password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required autoComplete="current-password" className="field" />
                        {form.errors.password && <p className="mt-1 text-xs text-rose-600">{form.errors.password}</p>}
                    </div>
                    <label className="flex items-center gap-2 text-xs text-ruma-body">
                        <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} className="rounded border-ruma-line text-ruma-green focus:ring-ruma-green/30" />
                        Ingat saya
                    </label>
                    <button type="submit" disabled={form.processing} className="btn-primary w-full">Masuk</button>
                </form>

                <p className="mt-5 text-center text-xs text-ruma-mute">
                    Belum punya akun? <Link href="/daftar" className="font-semibold text-ruma-green hover:underline">Buat akun admin</Link>
                </p>
            </div>
        </div>
    );
}

Login.layout = (page) => <PublicLayout>{page}</PublicLayout>;