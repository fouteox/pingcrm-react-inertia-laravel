import '../css/app.css';
import './echo';

import { LayoutProvider } from '@/contexts/page-context';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import React from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { initI18n, setLocale } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Ping CRM';

interface PageModule {
    default: React.ComponentType & {
        layout?: (page: React.ReactNode) => React.ReactNode;
    };
}

void createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'));

        page.then((module) => {
            const isAuthRoute =
                name.toLowerCase().includes('login') ||
                name.toLowerCase().includes('register') ||
                name.toLowerCase().includes('password') ||
                name.toLowerCase().includes('verification');

            (module as PageModule).default.layout = (page: React.ReactNode) => {
                if (isAuthRoute) {
                    return <AuthLayout>{page}</AuthLayout>;
                }

                return <AppLayout>{page}</AppLayout>;
            };
        });

        return page;
    },
    setup({ el, App, props }) {
        const currentLocale = props.initialPage.props.locale;
        const i18nInstance = initI18n(currentLocale, props.initialPage.props.translations || {});
        setLocale(currentLocale);

        const AppWithProviders = (
            <LayoutProvider>
                <I18nextProvider i18n={i18nInstance}>
                    <App {...props} />
                </I18nextProvider>
            </LayoutProvider>
        );

        if (import.meta.env.SSR) {
            hydrateRoot(el, AppWithProviders);
            return;
        }

        createRoot(el).render(AppWithProviders);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
