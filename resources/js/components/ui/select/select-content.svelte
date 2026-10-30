<script lang="ts">
    import { Select as SelectPrimitive } from 'bits-ui';
    import { cn, type WithoutChild } from '@/lib/utils';

    let { ref = $bindable(null), class: className, sideOffset = 4, children, ...restProps }: WithoutChild<SelectPrimitive.ContentProps> = $props();
</script>

<SelectPrimitive.Portal>
    <SelectPrimitive.Content
        bind:ref
        {sideOffset}
        data-slot="select-content"
        class={cn(
            'relative isolate z-50 max-h-(--bits-select-content-available-height) w-(--bits-select-anchor-width) min-w-36 origin-(--bits-select-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10 duration-100 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
            className,
        )}
        {...restProps}
    >
        <SelectPrimitive.Viewport>
            {@render children?.()}
        </SelectPrimitive.Viewport>
    </SelectPrimitive.Content>
</SelectPrimitive.Portal>
