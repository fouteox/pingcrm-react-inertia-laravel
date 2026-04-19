import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { StrictMode } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { LayoutProvider } from '@/contexts/page-context';
import { ProcessingProvider } from '@/contexts/processing-context';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import { initI18n, setLocale } from './i18n';

configureEcho({
    broadcaster: import.meta.env.SSR ? 'null' : 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Ping CRM';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name === 'error') {
            return null;
        }

        if (name.startsWith('auth/')) {
            return AuthLayout;
        }

        return AppLayout;
    },
    setup: ({ el, App, props }) => {
        const locale = props.initialPage.props.locale;
        const i18nInstance = initI18n(locale, props.initialPage.props.translations ?? {});
        setLocale(locale);

        const appElement = (
            <StrictMode>
                <LayoutProvider>
                    <ProcessingProvider>
                        <I18nextProvider i18n={i18nInstance}>
                            <App {...props} />
                        </I18nextProvider>
                    </ProcessingProvider>
                </LayoutProvider>
            </StrictMode>
        );

        if (import.meta.env.SSR || el === null) {
            return appElement;
        }

        if (el.hasAttribute('data-server-rendered')) {
            hydrateRoot(el, appElement);
        } else {
            createRoot(el).render(appElement);
        }
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
