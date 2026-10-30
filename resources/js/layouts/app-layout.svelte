<script lang="ts">
    import type { Snippet } from 'svelte';
    import ReverbNotificationListener from '@/components/reverb-notification-listener.svelte';
    import { provideReverbNotifications } from '@/contexts/reverb-context.svelte';
    import AppLayoutTemplate from '@/layouts/app-sidebar-layout.svelte';
    import type { BreadcrumbItem } from '@/types';

    /**
     * Inertia renders the page inside this layout and passes it as `children`.
     * `breadcrumbs` comes from the `layout` export of each page (see pages/dashboard.svelte).
     */
    let { children, breadcrumbs = [] }: { children: Snippet; breadcrumbs?: BreadcrumbItem[] } = $props();

    // Same role as the React <ReverbExampleNotificationProvider>: a context for all descendants.
    provideReverbNotifications();
</script>

<AppLayoutTemplate {breadcrumbs}>
    {@render children()}
    <ReverbNotificationListener />
</AppLayoutTemplate>
