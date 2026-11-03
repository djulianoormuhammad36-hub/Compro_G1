/**
 * Kirim form (boleh berisi file) ke Laravel lewat fetch, lalu laporkan hasilnya apa adanya.
 * Dipakai di halaman yang butuh pesan error server yang jelas (mis. Info Kos).
 *
 * Hasil: { ok: true } | { ok: false, jenis: 'validasi'|'sesi'|'server'|'jaringan', ... }
 */
export async function kirimForm(url, data, method = 'PUT') {
    const fd = new FormData();

    for (const [kunci, nilai] of Object.entries(data)) {
        if (nilai === null || nilai === undefined) continue;

        if (typeof File !== 'undefined' && nilai instanceof File) fd.append(kunci, nilai, nilai.name);
        else if (typeof nilai === 'boolean') fd.append(kunci, nilai ? '1' : '0');
        else fd.append(kunci, nilai);
    }

    fd.append('_method', method); // Laravel membaca ini sebagai PUT/PATCH/DELETE

    const xsrf = decodeURIComponent((document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/) || [])[1] ?? '');

    let res;
    try {
        res = await fetch(url, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrf },
        });
    } catch (e) {
        return { ok: false, jenis: 'jaringan', pesan: `Tidak bisa menghubungi server: ${e.message}` };
    }

    const teks = await res.text();
    let json = null;
    try { json = JSON.parse(teks); } catch { /* bukan JSON (mis. halaman error HTML) */ }

    if (res.ok) return { ok: true, pesan: json?.message ?? 'Tersimpan.' };

    if (res.status === 422) {
        const errors = Object.fromEntries(Object.entries(json?.errors ?? {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
        return { ok: false, jenis: 'validasi', errors };
    }

    if (res.status === 419) return { ok: false, jenis: 'sesi', pesan: 'Sesi habis (419). Muat ulang halaman, lalu coba lagi.' };
    if (res.status === 401 || res.status === 403) return { ok: false, jenis: 'sesi', pesan: `Akses ditolak (${res.status}). Login ulang sebagai admin.` };

    return {
        ok: false,
        jenis: 'server',
        status: res.status,
        pesan: json?.message || teks.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 400) || `Server membalas ${res.status}`,
        detail: json?.exception ? `${json.exception} di ${String(json.file).split(/[\\/]/).slice(-2).join('/')}:${json.line}` : null,
    };
}
