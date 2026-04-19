import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';

export function AppSidebarHeader() {
    return (
        <header className="sticky top-0 z-50 flex h-16 shrink-0 items-center gap-2 rounded-t-xl border-b border-sidebar-border/50 bg-background px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs />
            </div>
        </header>
    );
}
