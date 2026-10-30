<script lang="ts">
    import { Popover as PopoverPrimitive } from 'bits-ui';
    import { cn } from '@/lib/utils';

    let { ref = $bindable(null), class: className, sideOffset = 4, align = 'center', ...restProps }: PopoverPrimitive.ContentProps = $props();
</script>

<PopoverPrimitive.Portal>
    <PopoverPrimitive.Content
        bind:ref
        {sideOffset}
        {align}
        data-slot="popover-content"
        class={cn(
            'z-50 flex w-72 origin-(--bits-popover-content-transform-origin) flex-col gap-4 rounded-md bg-popover p-4 text-sm text-popover-foreground shadow-md ring-1 ring-foreground/10 outline-hidden duration-100 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
            className,
        )}
        {...restProps}
    />
</PopoverPrimitive.Portal>
