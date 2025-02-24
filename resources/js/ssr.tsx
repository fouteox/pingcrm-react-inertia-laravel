import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { RouteName } from 'ziggy-js';
import { route } from '../../vendor/tightenco/ziggy';
import { I18nProvider } from './Components/I18nProvider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.tsx`,
                import.meta.glob('./Pages/**/*.tsx'),
            ),
        setup: ({ App, props }) => {
            /* eslint-disable */
            // @ts-expect-error
            global.route<RouteName> = (name, params, absolute) =>
                route(name, params as any, absolute, {
                    ...page.props.ziggy,
                    location: new URL(page.props.ziggy.location),
                });
            /* eslint-enable */

            /* eslint-disable */
            const req = (page as any).req;
            let locale = page.props.locale;

            if (req?.headers?.cookie) {
                const cookies = req.headers.cookie.split(';');
                const localeCookie = cookies
                    .find((cookie: string) =>
                        cookie.trim().startsWith('locale='),
                    )
                    ?.split('=')[1];

                if (localeCookie) {
                    locale = localeCookie;
                }
            }

            return (
                <I18nProvider
                    initialI18nStore={page.props.translations || {}}
                    initialLanguage={locale}
                >
                    <App {...props} />
                </I18nProvider>
            );
        },
    }),
);
