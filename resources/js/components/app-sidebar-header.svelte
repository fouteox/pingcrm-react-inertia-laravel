<script lang="ts">
    import Breadcrumbs from '@/components/breadcrumbs.svelte';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import type { BreadcrumbItem } from '@/types';

    let { breadcrumbs }: { breadcrumbs: BreadcrumbItem[] } = $props();
</script>

<header
    class="sticky top-0 z-50 flex h-16 shrink-0 items-center gap-2 rounded-t-xl border-b border-sidebar-border/50 bg-background px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
>
    <div class="flex items-center gap-2">
        <SidebarTrigger class="-ml-1" />
        <Breadcrumbs {breadcrumbs} />
    </div>
</header>
