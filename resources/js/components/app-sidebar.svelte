<script lang="ts">
    import Building from '@lucide/svelte/icons/building';
    import ChevronsLeftRightEllipsis from '@lucide/svelte/icons/chevrons-left-right-ellipsis';
    import Contact from '@lucide/svelte/icons/contact';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import SparklesIcon from '@lucide/svelte/icons/sparkles';
    import Users from '@lucide/svelte/icons/users';
    import AppLogo from '@/components/app-logo.svelte';
    import MobileAwareLink from '@/components/mobile-aware-link.svelte';
    import NavFooter from '@/components/nav-footer.svelte';
    import NavMain from '@/components/nav-main.svelte';
    import NavUser from '@/components/nav-user.svelte';
    import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
    import type { NavItem } from '@/types';
    import { dashboard, fadogen } from '@/wayfinder/routes';
    import contacts from '@/wayfinder/routes/contacts';
    import organizations from '@/wayfinder/routes/organizations';
    import reverb from '@/wayfinder/routes/reverb';
    import users from '@/wayfinder/routes/users';

    // The component script runs once per instance, so no `useMemo` is needed.
    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'Organization', count: 2, href: organizations.index(), icon: Building },
        { title: 'Contact', count: 2, href: contacts.index(), icon: Contact },
        { title: 'User', count: 2, href: users.index(), icon: Users },
        { title: 'Reverb Demo', href: reverb.index(), icon: ChevronsLeftRightEllipsis },
        { title: 'Fadogen', href: fadogen(), icon: SparklesIcon },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg">
                    {#snippet child({ props })}
                        <MobileAwareLink {...props} href={dashboard()} prefetch>
                            <AppLogo />
                        </MobileAwareLink>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavFooter />
        <NavUser />
    </SidebarFooter>
</Sidebar>
