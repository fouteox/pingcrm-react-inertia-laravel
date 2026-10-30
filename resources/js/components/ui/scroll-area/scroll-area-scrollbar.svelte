<script lang="ts">
    import { ScrollArea as ScrollAreaPrimitive } from 'bits-ui';
    import { cn, type WithoutChild } from '@/lib/utils';

    let { ref = $bindable(null), class: className, orientation = 'vertical', ...restProps }: WithoutChild<ScrollAreaPrimitive.ScrollbarProps> = $props();
</script>

<ScrollAreaPrimitive.Scrollbar
    bind:ref
    {orientation}
    data-slot="scroll-area-scrollbar"
    class={cn(
        'flex touch-none p-px transition-colors select-none data-[orientation=horizontal]:h-2.5 data-[orientation=horizontal]:flex-col data-[orientation=horizontal]:border-t data-[orientation=horizontal]:border-t-transparent data-[orientation=vertical]:h-full data-[orientation=vertical]:w-2.5 data-[orientation=vertical]:border-l data-[orientation=vertical]:border-l-transparent',
        className,
    )}
    {...restProps}
>
    <ScrollAreaPrimitive.Thumb data-slot="scroll-area-thumb" class="relative flex-1 rounded-full bg-border" />
</ScrollAreaPrimitive.Scrollbar>
