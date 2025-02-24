import '../css/app.css';
import './echo';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { I18nProvider } from './Components/I18nProvider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const getCookieLocale = () => {
    const matches = document.cookie.match(/locale=([^;]+)/);
    return matches ? matches[1] : null;
};

void createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const locale = import.meta.env.SSR
            ? props.initialPage.props.locale
            : getCookieLocale() || props.initialPage.props.locale;

        const AppWithI18n = (
            <I18nProvider
                initialI18nStore={props.initialPage.props.translations || {}}
                initialLanguage={locale}
            >
                <App {...props} />
            </I18nProvider>
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
