import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import React, { ReactNode } from 'react';
import FlashMessages from '@/components/flash-messages';
import { ReverbExampleNotificationProvider } from '@/contexts/ReverbExampleNotificationContext';
import { ReverbNotificationListener } from '@/components/ReverbNotificationListener';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        <ReverbExampleNotificationProvider>
            {children}
            <FlashMessages />
            <ReverbNotificationListener />
        </ReverbExampleNotificationProvider>
    </AppLayoutTemplate>
);
