import AppLogo from '@/components/app-logo';
import { MobileAwareLink } from '@/components/mobile-aware-link';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard, fadogen } from '@/routes';
import contacts from '@/routes/contacts';
import organizations from '@/routes/organizations';
import reverb from '@/routes/reverb';
import users from '@/routes/users';
import { type NavItem } from '@/types';
import { Building, ChevronsLeftRightEllipsis, Contact, LayoutGrid, SparklesIcon, Users } from 'lucide-react';
import React from 'react';

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
                        <SidebarMenuButton size="lg" asChild>
                            <MobileAwareLink href={dashboard()} prefetch>
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
