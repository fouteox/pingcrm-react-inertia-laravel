<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppShell from '@/components/app-shell.svelte';
    import AppSidebarHeader from '@/components/app-sidebar-header.svelte';
    import AppSidebar from '@/components/app-sidebar.svelte';
    import { SidebarInset } from '@/components/ui/sidebar';
    import type { BreadcrumbItem } from '@/types';

    let { children, breadcrumbs }: { children: Snippet; breadcrumbs: BreadcrumbItem[] } = $props();
</script>

<AppShell>
    <AppSidebar />

    <SidebarInset>
        <AppSidebarHeader {breadcrumbs} />
        <div class="p-4">{@render children()}</div>
    </SidebarInset>
</AppShell>
