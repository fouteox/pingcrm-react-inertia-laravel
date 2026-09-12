import { usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';

interface AppShellProps {
    children: ReactNode;
}

export function AppShell({ children }: AppShellProps) {
    const { sidebarOpen } = usePage().props;

    return <SidebarProvider defaultOpen={sidebarOpen}>{children}</SidebarProvider>;
}
