import '../css/app.css';
import { createInertiaApp } from '@inertiajs/svelte';
import { configureEcho } from '@laravel/echo-svelte';
import { hydrate, mount } from 'svelte';
import { render as renderToString } from 'svelte/server';
import { initializeTheme } from '@/hooks/use-appearance.svelte';
import { setTranslationContext } from '@/hooks/use-translation.svelte';
import AppLayout from '@/layouts/app-layout.svelte';
import AuthLayout from '@/layouts/auth-layout.svelte';
import { initI18n } from './i18n';
import Root from './root.svelte';

/**
 * The `@inertiajs/vite` plugin finds the pages in `resources/js/pages` and
 * generates the SSR entry, so only the app setup lives here. Page titles are
 * set by `components/app-head.svelte`.
 */
void createInertiaApp({
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
        if (import.meta.env.SSR) {
            configureEcho({ broadcaster: 'null' });
        } else {
            const reverb = props.initialPage.props.reverb;

            configureEcho({
                broadcaster: 'reverb',
                key: reverb.key,
                wsHost: reverb.host,
                wsPort: reverb.port,
                wssPort: reverb.port,
                forceTLS: reverb.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        }

        const locale = props.initialPage.props.locale;
        const i18nInstance = initI18n(locale, props.initialPage.props.translations ?? {});

        // Svelte context set before mounting is visible to every component
        // (the equivalent of wrapping <App> in <I18nextProvider>).
        const context = new Map();
        setTranslationContext(i18nInstance, context);

        const rootProps = { App, appProps: props };

        if (import.meta.env.SSR || el === null) {
            return renderToString(Root, { props: rootProps, context });
        }

        if (el.hasAttribute('data-server-rendered')) {
            hydrate(Root, { target: el, props: rootProps, context });
        } else {
            mount(Root, { target: el, props: rootProps, context });
        }
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
