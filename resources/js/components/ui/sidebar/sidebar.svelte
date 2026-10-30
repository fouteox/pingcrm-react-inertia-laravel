<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
    import { cn, type WithElementRef } from '@/lib/utils';
    import { SIDEBAR_WIDTH_MOBILE, useSidebar } from './context.svelte';

    let {
        ref = $bindable(null),
        side = 'left',
        variant = 'sidebar',
        collapsible = 'offcanvas',
        class: className,
        children,
        ...restProps
    }: WithElementRef<HTMLAttributes<HTMLDivElement>> & {
        side?: 'left' | 'right';
        variant?: 'sidebar' | 'floating' | 'inset';
        collapsible?: 'offcanvas' | 'icon' | 'none';
    } = $props();

    const sidebar = useSidebar();
</script>

{#if collapsible === 'none'}
    <div bind:this={ref} data-slot="sidebar" class={cn('flex h-full w-(--sidebar-width) flex-col bg-sidebar text-sidebar-foreground', className)} {...restProps}>
        {@render children?.()}
    </div>
{:else if sidebar.isMobile}
    <!-- `bind:open={() => getter, (v) => setter}` is a "function binding": two-way binding onto a class field. -->
    <Sheet bind:open={() => sidebar.openMobile, (value) => sidebar.setOpenMobile(value)}>
        <SheetContent
            data-sidebar="sidebar"
            data-slot="sidebar"
            data-mobile="true"
            class="w-(--sidebar-width) bg-sidebar p-0 text-sidebar-foreground [&>button]:hidden"
            style="--sidebar-width: {SIDEBAR_WIDTH_MOBILE};"
            {side}
        >
            <div class="sr-only">
                <SheetTitle>Sidebar</SheetTitle>
                <SheetDescription>Displays the mobile sidebar.</SheetDescription>
            </div>
            <div class="flex h-full w-full flex-col">
                {@render children?.()}
            </div>
        </SheetContent>
    </Sheet>
{:else}
    <div
        bind:this={ref}
        class="group peer hidden text-sidebar-foreground md:block"
        data-state={sidebar.state}
        data-collapsible={sidebar.state === 'collapsed' ? collapsible : ''}
        data-variant={variant}
        data-side={side}
        data-slot="sidebar"
    >
        <!-- This is what handles the sidebar gap on desktop -->
        <div
            data-slot="sidebar-gap"
            class={cn(
                'relative w-(--sidebar-width) bg-transparent transition-[width] duration-200 ease-linear',
                'group-data-[collapsible=offcanvas]:w-0',
                'group-data-[side=right]:rotate-180',
                variant === 'floating' || variant === 'inset'
                    ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4)))]'
                    : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon)',
            )}
        ></div>
        <div
            data-slot="sidebar-container"
            data-side={side}
            class={cn(
                'fixed inset-y-0 z-10 hidden h-svh w-(--sidebar-width) transition-[left,right,width] duration-200 ease-linear data-[side=left]:left-0 data-[side=left]:group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)] data-[side=right]:right-0 data-[side=right]:group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)] md:flex',
                variant === 'floating' || variant === 'inset'
                    ? 'p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4))+2px)]'
                    : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon) group-data-[side=left]:border-r group-data-[side=right]:border-l',
                className,
            )}
            {...restProps}
        >
            <div
                data-sidebar="sidebar"
                data-slot="sidebar-inner"
                class="flex size-full flex-col bg-sidebar group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:shadow-sm group-data-[variant=floating]:ring-1 group-data-[variant=floating]:ring-sidebar-border"
            >
                {@render children?.()}
            </div>
        </div>
    </div>
{/if}
