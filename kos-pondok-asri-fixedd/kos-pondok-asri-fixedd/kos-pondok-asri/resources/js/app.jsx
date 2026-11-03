import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';

createInertiaApp({
    title: (title) => (title ? `${title} - Kos` : 'Kos'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.jsx');
        const page = pages[`./pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Halaman React tidak ditemukan: ${name}`);
        }

        return page();
    },
    progress: { color: '#215442' },
});
