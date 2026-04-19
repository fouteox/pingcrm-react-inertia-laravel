import { ReactNode } from 'react';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { SidebarInset } from '@/components/ui/sidebar';

export default function AppSidebarLayout({ children }: { children: ReactNode }) {
    return (
        <AppShell>
            <AppSidebar />

            <SidebarInset>
                <AppSidebarHeader />
                <div className="p-4">{children}</div>
            </SidebarInset>
        </AppShell>
    );
}
