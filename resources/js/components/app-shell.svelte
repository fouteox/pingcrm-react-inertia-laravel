<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { SidebarProvider } from '@/components/ui/sidebar';

    let { children }: { children: Snippet } = $props();

    // `page` is a global reactive object from the Inertia adapter (the React `usePage()`).
    // The cookie value is only read once: afterwards the provider owns the state.
    // svelte-ignore state_referenced_locally
    let open = $state(page.props.sidebarOpen);
</script>

<SidebarProvider bind:open>
    {@render children()}
</SidebarProvider>
