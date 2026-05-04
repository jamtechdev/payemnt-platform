import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';
import axios from 'axios';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// When tab becomes visible again, ping to keep session alive
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        axios.get('/ping').catch(() => {
            // Session expired — redirect to login
            window.location.href = '/login';
        });
    }
});

// Auto-refresh CSRF token and retry on 419
router.on('invalid', (e) => {
    if (e.detail.response.status === 419) {
        e.preventDefault();
        axios.get('/sanctum/csrf-cookie').then(() => {
            router.reload();
        });
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        const legacyPages = import.meta.glob('./Pages/**/*.tsx');

        const lower = `./pages/${name}.tsx`;
        const upper = `./Pages/${name}.tsx`;

        if (pages[lower]) {
            return resolvePageComponent(lower, pages);
        }

        if (legacyPages[upper]) {
            return resolvePageComponent(upper, legacyPages);
        }

        return resolvePageComponent(lower, pages);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
