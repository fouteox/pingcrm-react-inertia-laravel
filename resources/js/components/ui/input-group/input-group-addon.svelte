<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let {
        ref = $bindable(null),
        class: className,
        align = 'inline-start',
        children,
        ...restProps
    }: WithElementRef<HTMLAttributes<HTMLDivElement>> & { align?: 'inline-start' | 'inline-end' } = $props();
</script>

<div
    bind:this={ref}
    role="group"
    data-slot="input-group-addon"
    data-align={align}
    class={cn(
        "flex h-auto cursor-text items-center justify-center gap-2 py-1.5 text-sm font-medium text-muted-foreground select-none group-data-[disabled=true]/input-group:opacity-50 [&>svg:not([class*='size-'])]:size-4",
        align === 'inline-start' ? 'order-first pl-2 has-[>button]:-ml-1' : 'order-last pr-2 has-[>button]:-mr-1',
        className,
    )}
    {...restProps}
>
    {@render children?.()}
</div>
