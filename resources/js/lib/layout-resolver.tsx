import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import React from 'react';

export interface PageModule {
    default: React.ComponentType & {
        layout?: (page: React.ReactNode) => React.ReactNode;
    };
}

export function isPageModule(module: unknown): module is PageModule {
    return module !== null && typeof module === 'object' && 'default' in module && typeof (module as { default: unknown }).default === 'function';
}

export function applyLayoutToPage(module: unknown, pageName: string): void {
    if (!isPageModule(module)) {
        console.error('Module provided is not a valid PageModule');
        return;
    }

    const isAuthRoute =
        pageName.toLowerCase().includes('login') ||
        pageName.toLowerCase().includes('register') ||
        pageName.toLowerCase().includes('password') ||
        pageName.toLowerCase().includes('verification');

    module.default.layout = (page: React.ReactNode) => {
        if (isAuthRoute) {
            return <AuthLayout>{page}</AuthLayout>;
        }

        return <AppLayout>{page}</AppLayout>;
    };
}
