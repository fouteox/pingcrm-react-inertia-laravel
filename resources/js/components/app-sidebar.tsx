import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Building, ChevronsLeftRightEllipsis, Contact, LayoutGrid, Users } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
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
];

export function AppSidebar() {
    return (
        <Sidebar>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('dashboard')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
