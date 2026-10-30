<script lang="ts">
    import type { App as InertiaApp } from '@inertiajs/svelte';
    import type { ComponentProps } from 'svelte';
    import FlashMessages from '@/components/flash-messages.svelte';

    /**
     * Root component: the Inertia <App> (current page + layouts) and the
     * toaster next to it, like the React tree `<App /><FlashMessages />`.
     */
    let { App, appProps }: { App: typeof InertiaApp; appProps: ComponentProps<typeof InertiaApp> } = $props();
</script>

<App {...appProps} />
<FlashMessages />
