<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import MobileAwareLink from '@/components/mobile-aware-link.svelte';
    import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import type { NavItem } from '@/types';

    let { items = [] }: { items: NavItem[] } = $props();

    const { t } = useTranslation();

    // Re-evaluated automatically whenever Inertia navigates (page.url changes).
    const currentPath = $derived(page.url.split(/[?#]/, 1)[0]);

    const getTranslatedTitle = (item: NavItem) => t(item.title, item.count !== undefined ? { count: item.count } : undefined);
</script>

<SidebarGroup class="px-2 py-0">
    <SidebarMenu>
        {#each items as item (item.title)}
            {@const href = typeof item.href === 'string' ? item.href : item.href.url}
            {@const isActive = currentPath === href || (href !== '/' && currentPath.startsWith(`${href}/`))}
            <SidebarMenuItem>
                <SidebarMenuButton {isActive} tooltipContent={getTranslatedTitle(item)}>
                    {#snippet child({ props })}
                        <MobileAwareLink {...props} href={item.href} prefetch aria-current={isActive ? 'page' : undefined}>
                            {#if item.icon}
                                <item.icon />
                            {/if}
                            <span>{getTranslatedTitle(item)}</span>
                        </MobileAwareLink>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        {/each}
    </SidebarMenu>
</SidebarGroup>
