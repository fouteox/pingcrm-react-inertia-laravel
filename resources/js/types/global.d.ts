import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { route as routeFn } from 'ziggy-js';
import { SharedData as AppPageProps } from './';

declare global {
    const route: typeof routeFn;
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
