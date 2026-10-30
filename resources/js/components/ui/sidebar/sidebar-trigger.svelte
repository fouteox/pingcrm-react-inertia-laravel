<script lang="ts">
    import PanelLeftIcon from '@lucide/svelte/icons/panel-left';
    import { Button, type ButtonProps } from '@/components/ui/button';
    import { cn } from '@/lib/utils';
    import { useSidebar } from './context.svelte';

    let {
        ref = $bindable(null),
        class: className,
        onclick,
        ...restProps
    }: Omit<ButtonProps, 'onclick'> & { onclick?: (event: MouseEvent) => void } = $props();

    const sidebar = useSidebar();
</script>

<Button
    bind:ref
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon-sm"
    class={cn(className)}
    type="button"
    onclick={(event) => {
        onclick?.(event);
        sidebar.toggle();
    }}
    {...restProps}
>
    <PanelLeftIcon />
    <span class="sr-only">Toggle Sidebar</span>
</Button>
