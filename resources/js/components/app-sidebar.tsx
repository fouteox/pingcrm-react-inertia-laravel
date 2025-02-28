import AppLogo from '@/components/app-logo';
import { MobileAwareLink } from '@/components/mobile-aware-link';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Building, ChevronsLeftRightEllipsis, Contact, LayoutGrid, SparklesIcon, Users } from 'lucide-react';
import React from 'react';

export function AppSidebar() {
    const mainNavItems: NavItem[] = React.useMemo(
        () => [
            {
                title: 'Dashboard',
                url: route('dashboard', {}, false),
                icon: LayoutGrid,
            },
            {
                title: 'Organization',
                count: 2,
                url: route('organizations.index', {}, false),
                icon: Building,
            },
            {
                title: 'Contact',
                count: 2,
                url: route('contacts.index', {}, false),
                icon: Contact,
            },
            {
                title: 'User',
                count: 2,
                url: route('users.index', {}, false),
                icon: Users,
            },
            {
                title: 'Reverb Demo',
                url: route('reverb.index', {}, false),
                icon: ChevronsLeftRightEllipsis,
            },
            {
                title: 'Fadogen',
                url: route('fadogen', {}, false),
                icon: SparklesIcon,
            },
        ],
        [],
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <MobileAwareLink href={route('dashboard')} prefetch>
                                <AppLogo />
                            </MobileAwareLink>
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
    );
}
