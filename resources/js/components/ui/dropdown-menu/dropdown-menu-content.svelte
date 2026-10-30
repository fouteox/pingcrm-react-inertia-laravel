<script lang="ts">
    import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
    import { cn } from '@/lib/utils';

    let { ref = $bindable(null), sideOffset = 4, align = 'start', class: className, ...restProps }: DropdownMenuPrimitive.ContentProps = $props();
</script>

<DropdownMenuPrimitive.Portal>
    <DropdownMenuPrimitive.Content
        bind:ref
        {sideOffset}
        {align}
        data-slot="dropdown-menu-content"
        class={cn(
            'z-50 max-h-(--bits-dropdown-menu-content-available-height) min-w-32 origin-(--bits-dropdown-menu-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10 duration-100 outline-none data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:overflow-hidden data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
            className,
        )}
        {...restProps}
    />
</DropdownMenuPrimitive.Portal>
