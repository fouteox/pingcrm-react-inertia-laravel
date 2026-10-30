<script lang="ts">
    import { Dialog as SheetPrimitive } from 'bits-ui';
    import { cn, type WithoutChildrenOrChild } from '@/lib/utils';
    import type { Snippet } from 'svelte';

    let {
        ref = $bindable(null),
        class: className,
        side = 'right',
        children,
        ...restProps
    }: WithoutChildrenOrChild<SheetPrimitive.ContentProps> & { side?: 'top' | 'right' | 'bottom' | 'left'; children?: Snippet } = $props();
</script>

<SheetPrimitive.Portal>
    <SheetPrimitive.Overlay
        data-slot="sheet-overlay"
        class="fixed inset-0 z-50 bg-black/10 supports-backdrop-filter:backdrop-blur-xs data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=closed]:animate-out data-[state=closed]:fade-out-0"
    />
    <SheetPrimitive.Content
        bind:ref
        data-slot="sheet-content"
        data-side={side}
        class={cn(
            'fixed z-50 flex flex-col gap-4 bg-popover bg-clip-padding text-sm text-popover-foreground shadow-lg transition ease-in-out data-[side=left]:inset-y-0 data-[side=left]:left-0 data-[side=left]:h-full data-[side=left]:w-3/4 data-[side=left]:border-r data-[side=left]:sm:max-w-sm data-[side=right]:inset-y-0 data-[side=right]:right-0 data-[side=right]:h-full data-[side=right]:w-3/4 data-[side=right]:border-l data-[side=right]:sm:max-w-sm data-[state=open]:animate-in data-[state=open]:duration-200 data-[state=open]:data-[side=left]:slide-in-from-left data-[state=open]:data-[side=right]:slide-in-from-right',
            className,
        )}
        {...restProps}
    >
        {@render children?.()}
    </SheetPrimitive.Content>
</SheetPrimitive.Portal>
