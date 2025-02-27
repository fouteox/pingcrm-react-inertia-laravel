import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { SidebarInset } from '@/components/ui/sidebar';
import { type BreadcrumbItem } from '@/types';
import { ReactNode } from 'react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell>
            <AppSidebar />

            <SidebarInset>
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="p-4">{children}</div>
            </SidebarInset>
        </AppShell>
    );
}
