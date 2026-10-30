<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { TooltipProvider } from '@/components/ui/tooltip';
    import { cn, type WithElementRef } from '@/lib/utils';
    import { setSidebar, SIDEBAR_COOKIE_MAX_AGE, SIDEBAR_COOKIE_NAME, SIDEBAR_WIDTH, SIDEBAR_WIDTH_ICON } from './context.svelte';

    let {
        ref = $bindable(null),
        open = $bindable(true),
        onOpenChange = () => {},
        class: className,
        style,
        children,
        ...restProps
    }: WithElementRef<HTMLAttributes<HTMLDivElement>> & {
        open?: boolean;
        onOpenChange?: (open: boolean) => void;
    } = $props();

    // Getter / setter pair: the context always reads the latest `open` prop
    // and writes it back through the bindable, persisting it in a cookie.
    const sidebar = setSidebar({
        open: () => open,
        setOpen: (value: boolean) => {
            open = value;
            onOpenChange(value);
            document.cookie = `${SIDEBAR_COOKIE_NAME}=${open}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`;
        },
    });
</script>

<!-- `<svelte:window>` adds and removes the listener with the component lifecycle (no useEffect cleanup). -->
<svelte:window onkeydown={sidebar.handleShortcutKeydown} />

<TooltipProvider delayDuration={0}>
    <div
        bind:this={ref}
        data-slot="sidebar-wrapper"
        style="--sidebar-width: {SIDEBAR_WIDTH}; --sidebar-width-icon: {SIDEBAR_WIDTH_ICON}; {style}"
        class={cn('group/sidebar-wrapper flex min-h-svh w-full has-data-[variant=inset]:bg-sidebar', className)}
        {...restProps}
    >
        {@render children?.()}
    </div>
</TooltipProvider>
