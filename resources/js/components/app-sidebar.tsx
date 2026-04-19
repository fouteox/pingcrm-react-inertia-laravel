import { Building, ChevronsLeftRightEllipsis, Contact, LayoutGrid, SparklesIcon, Users } from 'lucide-react';
import React from 'react';
import AppLogo from '@/components/app-logo';
import { MobileAwareLink } from '@/components/mobile-aware-link';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { dashboard, fadogen } from '@/wayfinder/routes';
import contacts from '@/wayfinder/routes/contacts';
import organizations from '@/wayfinder/routes/organizations';
import reverb from '@/wayfinder/routes/reverb';
import users from '@/wayfinder/routes/users';

export function AppSidebar() {
    const mainNavItems: NavItem[] = React.useMemo(
        () => [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Organization',
                count: 2,
                href: organizations.index(),
                icon: Building,
            },
            {
                title: 'Contact',
                count: 2,
                href: contacts.index(),
                icon: Contact,
            },
            {
                title: 'User',
                count: 2,
                href: users.index(),
                icon: Users,
            },
            {
                title: 'Reverb Demo',
                href: reverb.index(),
                icon: ChevronsLeftRightEllipsis,
            },
            {
                title: 'Fadogen',
                href: fadogen(),
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
                        <SidebarMenuButton size="lg" render={<MobileAwareLink href={dashboard()} prefetch />}>
                            <AppLogo />
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
