import { LayoutProvider } from '@/contexts/page-context';
import { applyLayoutToPage } from '@/lib/layout-resolver';
import { Page } from '@inertiajs/core';
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { I18nextProvider } from 'react-i18next';
import { type RouteName, route } from 'ziggy-js';
import { initI18n, setLocale } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page: Page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => {
            const pageModule = resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'));

            pageModule.then((module) => {
                applyLayoutToPage(module, name);
            });

            return pageModule;
        },
        setup: ({ App, props }) => {
            /* eslint-disable */
            // @ts-expect-error
            global.route<RouteName> = (name, params, absolute) =>
                route(name, params as any, absolute, {
                    ...page.props.ziggy,
                    location: new URL(page.props.ziggy.location),
                });
            /* eslint-enable */

            const currentLocale = page.props.locale;
            const i18nInstance = initI18n(currentLocale, page.props.translations || {});
            setLocale(currentLocale);

            return (
                <LayoutProvider>
                    <I18nextProvider i18n={i18nInstance}>
                        <App {...props} />
                    </I18nextProvider>
                </LayoutProvider>
            );
        },
    }),
);
