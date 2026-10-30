<script lang="ts">
    import { ScrollArea as ScrollAreaPrimitive } from 'bits-ui';
    import { cn, type WithoutChild } from '@/lib/utils';
    import Scrollbar from './scroll-area-scrollbar.svelte';

    let {
        ref = $bindable(null),
        class: className,
        orientation = 'vertical',
        children,
        ...restProps
    }: WithoutChild<ScrollAreaPrimitive.RootProps> & { orientation?: 'vertical' | 'horizontal' | 'both' } = $props();
</script>

<ScrollAreaPrimitive.Root bind:ref data-slot="scroll-area" class={cn('relative', className)} {...restProps}>
    <ScrollAreaPrimitive.Viewport
        data-slot="scroll-area-viewport"
        class="size-full rounded-[inherit] transition-[color,box-shadow] outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-1"
    >
        {@render children?.()}
    </ScrollAreaPrimitive.Viewport>
    {#if orientation === 'vertical' || orientation === 'both'}
        <Scrollbar orientation="vertical" />
    {/if}
    {#if orientation === 'horizontal' || orientation === 'both'}
        <Scrollbar orientation="horizontal" />
    {/if}
    <ScrollAreaPrimitive.Corner />
</ScrollAreaPrimitive.Root>
