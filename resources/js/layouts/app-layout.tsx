import FlashMessages from '@/components/flash-messages';
import { ReverbNotificationListener } from '@/components/reverb-notification-listener';
import { ReverbExampleNotificationProvider } from '@/contexts/reverb-context';
import AppLayoutTemplate from '@/layouts/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children }: AppLayoutProps) => (
    <AppLayoutTemplate>
        <ReverbExampleNotificationProvider>
            {children}
            <FlashMessages />
            <ReverbNotificationListener />
        </ReverbExampleNotificationProvider>
    </AppLayoutTemplate>
);
