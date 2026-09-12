import { type ReactNode } from 'react';
import { ReverbNotificationListener } from '@/components/reverb-notification-listener';
import { ReverbExampleNotificationProvider } from '@/contexts/reverb-context';
import AppLayoutTemplate from '@/layouts/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default function AppLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            <ReverbExampleNotificationProvider>
                {children}
                <ReverbNotificationListener />
            </ReverbExampleNotificationProvider>
        </AppLayoutTemplate>
    );
}
