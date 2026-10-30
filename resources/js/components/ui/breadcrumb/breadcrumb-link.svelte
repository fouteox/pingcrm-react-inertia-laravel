<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { HTMLAnchorAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    /**
     * `child` is the Svelte equivalent of base-ui's `render` prop: instead of
     * rendering an `<a>`, the parent receives the computed props and renders
     * its own element (here an Inertia `<Link>`).
     */
    let {
        ref = $bindable(null),
        class: className,
        href = undefined,
        child,
        children,
        ...restProps
    }: WithElementRef<HTMLAnchorAttributes> & { child?: Snippet<[{ props: Record<string, unknown> }]> } = $props();

    const attrs = $derived({
        'data-slot': 'breadcrumb-link',
        class: cn('transition-colors hover:text-foreground', className),
        href,
        ...restProps,
    });
</script>

{#if child}
    {@render child({ props: attrs })}
{:else}
    <a bind:this={ref} {...attrs}>
        {@render children?.()}
    </a>
{/if}
