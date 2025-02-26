import FlashMessages from '@/components/flash-messages';
import { ReverbNotificationListener } from '@/components/ReverbNotificationListener';
import { ReverbExampleNotificationProvider } from '@/contexts/ReverbExampleNotificationContext';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { ReactNode } from 'react';

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
