<script lang="ts">
    import { Tooltip as TooltipPrimitive } from 'bits-ui';
    import { cn } from '@/lib/utils';

    let { ref = $bindable(null), class: className, sideOffset = 4, side = 'top', children, ...restProps }: TooltipPrimitive.ContentProps = $props();
</script>

<TooltipPrimitive.Portal>
    <TooltipPrimitive.Content
        bind:ref
        {sideOffset}
        {side}
        data-slot="tooltip-content"
        class={cn(
            'z-50 inline-flex w-fit max-w-xs origin-(--bits-tooltip-content-transform-origin) items-center gap-1.5 rounded-md bg-foreground px-3 py-1.5 text-xs text-background data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=delayed-open]:animate-in data-[state=delayed-open]:fade-in-0 data-[state=delayed-open]:zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
            className,
        )}
        {...restProps}
    >
        {@render children?.()}
    </TooltipPrimitive.Content>
</TooltipPrimitive.Portal>
