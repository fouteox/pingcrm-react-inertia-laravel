import { createInertiaApp } from '@inertiajs/react';
import { renderToString } from 'react-dom/server';
import { I18nextProvider } from 'react-i18next';
import { expect, it } from 'vite-plus/test';
import { initI18n } from '@/i18n';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import Login from '@/pages/auth/login';
import Dashboard from '@/pages/dashboard';

function page(component: string) {
    return {
        component,
        props: {
            auth: { user: null },
            locale: 'en',
            translations: null,
            sidebarOpen: false,
            reverb: { key: '', host: 'localhost', port: 8080, scheme: 'http' },
            errors: {},
        },
        url: component === 'auth/login' ? '/login' : '/',
        version: null,
        rescuedProps: [],
        flash: {},
        rememberedState: {},
    };
}

it('renders the login heading and description in the initial server response', async () => {
    const response = await createInertiaApp({
        page: page('auth/login'),
        resolve: () => Login,
        layout: () => AuthLayout,
        render: renderToString,
        setup: ({ App, props }) => (
            <I18nextProvider i18n={initI18n('en', {})}>
                <App {...props} />
            </I18nextProvider>
        ),
    });

    expect(response.body).toMatch(/<h1[^>]*>Log in to your account<\/h1>/);
    expect(response.body).toContain('Enter your email and password below to log in');
});

it('renders the current breadcrumbs and closed sidebar before hydration', async () => {
    const response = await createInertiaApp({
        page: page('dashboard'),
        resolve: () => Dashboard,
        layout: () => AppLayout,
        render: renderToString,
        setup: ({ App, props }) => (
            <I18nextProvider i18n={initI18n('en', {})}>
                <App {...props} />
            </I18nextProvider>
        ),
    });

    expect(response.body).toMatch(/data-state="collapsed"[^>]*data-slot="sidebar"/);
    expect(response.body).toMatch(/data-slot="breadcrumb-page"[^>]*>Dashboard<\/span>/);
});
