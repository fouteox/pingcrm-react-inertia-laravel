<script lang="ts">
    import { AlertDialog as AlertDialogPrimitive } from 'bits-ui';
    import { cn, type WithoutChild } from '@/lib/utils';

    let {
        ref = $bindable(null),
        class: className,
        size = 'default',
        children,
        ...restProps
    }: WithoutChild<AlertDialogPrimitive.ContentProps> & { size?: 'default' | 'sm' } = $props();
</script>

<!-- Portal + overlay are bundled here, like in the React version. -->
<AlertDialogPrimitive.Portal>
    <AlertDialogPrimitive.Overlay
        data-slot="alert-dialog-overlay"
        class="fixed inset-0 isolate z-50 bg-black/10 duration-100 supports-backdrop-filter:backdrop-blur-xs data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=closed]:animate-out data-[state=closed]:fade-out-0"
    />
    <AlertDialogPrimitive.Content
        bind:ref
        data-slot="alert-dialog-content"
        data-size={size}
        class={cn(
            'group/alert-dialog-content fixed top-1/2 left-1/2 z-50 grid w-full -translate-x-1/2 -translate-y-1/2 gap-6 rounded-xl bg-popover p-6 text-popover-foreground ring-1 ring-foreground/10 duration-100 outline-none data-[size=default]:max-w-xs data-[size=sm]:max-w-xs data-[size=default]:sm:max-w-lg data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
            className,
        )}
        {...restProps}
    >
        {@render children?.()}
    </AlertDialogPrimitive.Content>
</AlertDialogPrimitive.Portal>
