<script lang="ts">
    import { AlertDialog as AlertDialogPrimitive } from 'bits-ui';
    import { buttonVariants, type ButtonSize, type ButtonVariant } from '@/components/ui/button';
    import { cn } from '@/lib/utils';

    let {
        ref = $bindable(null),
        class: className,
        variant = 'outline',
        size = 'default',
        ...restProps
    }: AlertDialogPrimitive.CancelProps & { variant?: ButtonVariant; size?: ButtonSize } = $props();
</script>

<AlertDialogPrimitive.Cancel bind:ref data-slot="alert-dialog-cancel" class={cn(buttonVariants({ variant, size }), className)} {...restProps} />
