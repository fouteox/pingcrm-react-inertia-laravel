<script lang="ts">
    import { Button, type ButtonProps } from '@/components/ui/button';
    import { cn } from '@/lib/utils';

    const sizes = {
        xs: "h-6 gap-1 rounded-[calc(var(--radius)-5px)] px-1.5 [&>svg:not([class*='size-'])]:size-3.5",
        sm: '',
        'icon-xs': 'size-6 rounded-[calc(var(--radius)-5px)] p-0 has-[>svg]:p-0',
        'icon-sm': 'size-8 p-0 has-[>svg]:p-0',
    };

    let {
        ref = $bindable(null),
        class: className,
        variant = 'ghost',
        size = 'xs',
        ...restProps
    }: Omit<ButtonProps, 'size'> & { size?: keyof typeof sizes } = $props();
</script>

<Button bind:ref data-size={size} {variant} class={cn('flex items-center gap-2 text-sm shadow-none', sizes[size], className)} {...restProps} />
