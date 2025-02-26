import '../css/app.css';
import './echo';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { initI18n, setLocale } from './i18n';
import { initializeTheme } from '@/hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const currentLocale = props.initialPage.props.locale;
        const i18nInstance = initI18n(
            currentLocale,
            props.initialPage.props.translations || {},
        );
        setLocale(currentLocale);

        const AppWithI18n = (
            <I18nextProvider i18n={i18nInstance}>
                <App {...props} />
            </I18nextProvider>
        );

        if (import.meta.env.SSR) {
            hydrateRoot(el, AppWithI18n);
            return;
        }

        createRoot(el).render(AppWithI18n);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
