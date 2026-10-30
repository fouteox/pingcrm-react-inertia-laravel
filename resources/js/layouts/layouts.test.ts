import { createInertiaApp } from '@inertiajs/svelte';
import { render } from 'svelte/server';
import { expect, it } from 'vite-plus/test';
import { setTranslationContext } from '@/hooks/use-translation.svelte';
import { initI18n } from '@/i18n';
import AppLayout from '@/layouts/app-layout.svelte';
import AuthLayout from '@/layouts/auth-layout.svelte';
import * as Login from '@/pages/auth/login.svelte';
import * as Dashboard from '@/pages/dashboard.svelte';

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

async function renderPage(component: string, module: typeof Login | typeof Dashboard, layout: typeof AuthLayout | typeof AppLayout) {
    const response = await createInertiaApp({
        page: page(component),
        resolve: () => module,
        layout: () => layout,
        setup: ({ App, props }) => {
            const context = new Map();
            setTranslationContext(initI18n('en', {}), context);

            return render(App, { props, context });
        },
    });

    // Svelte adds `<!---->` hydration markers to the server HTML; drop them to keep the assertions readable.
    return (response?.body ?? '').replace(/<!--[^>]*-->/g, '');
}

it('renders the login heading and description in the initial server response', async () => {
    const body = await renderPage('auth/login', Login, AuthLayout);

    expect(body).toMatch(/<h1[^>]*>Log in to your account<\/h1>/);
    expect(body).toContain('Enter your email and password below to log in');
});

it('renders the current breadcrumbs and closed sidebar before hydration', async () => {
    const body = await renderPage('dashboard', Dashboard, AppLayout);

    expect(body).toMatch(/data-state="collapsed"[^>]*data-slot="sidebar"/);
    expect(body).toMatch(/data-slot="breadcrumb-page"[^>]*>Dashboard<\/span>/);
});
