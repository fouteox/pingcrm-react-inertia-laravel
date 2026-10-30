<script lang="ts">
    import CircleCheckIcon from '@lucide/svelte/icons/circle-check';
    import InfoIcon from '@lucide/svelte/icons/info';
    import Loader2Icon from '@lucide/svelte/icons/loader-circle';
    import OctagonXIcon from '@lucide/svelte/icons/octagon-x';
    import TriangleAlertIcon from '@lucide/svelte/icons/triangle-alert';
    import { Toaster as Sonner, type ToasterProps } from 'svelte-sonner';
    import { appearance } from '@/hooks/use-appearance.svelte';

    let { ...restProps }: ToasterProps = $props();
</script>

<!--
    svelte-sonner takes its icons as snippets (named blocks passed as props)
    where the React version passed JSX elements in an `icons` object.
-->
<Sonner
    theme={appearance.resolved}
    class="toaster group"
    style="--normal-bg: var(--popover); --normal-text: var(--popover-foreground); --normal-border: var(--border); --border-radius: var(--radius);"
    toastOptions={{ classes: { toast: 'cn-toast' } }}
    {...restProps}
>
    {#snippet successIcon()}
        <CircleCheckIcon class="size-4" />
    {/snippet}
    {#snippet infoIcon()}
        <InfoIcon class="size-4" />
    {/snippet}
    {#snippet warningIcon()}
        <TriangleAlertIcon class="size-4" />
    {/snippet}
    {#snippet errorIcon()}
        <OctagonXIcon class="size-4" />
    {/snippet}
    {#snippet loadingIcon()}
        <Loader2Icon class="size-4 animate-spin" />
    {/snippet}
</Sonner>
