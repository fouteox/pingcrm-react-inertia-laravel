<script lang="ts">
    /**
     * The Svelte adapter has no <Head> component: Svelte's own <svelte:head>
     * does the job. This wrapper keeps the "Title - Ping CRM" format that the
     * React version configured with the `title` option of createInertiaApp.
     */
    let { title }: { title?: string } = $props();

    const appName = import.meta.env.VITE_APP_NAME || 'Ping CRM';
</script>

<svelte:head>
    <title>{title ? `${title} - ${appName}` : appName}</title>
</svelte:head>
