import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MobileAwareLink } from '@/components/mobile-aware-link';
import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { t } = useTranslation();
    const page = usePage();
    const currentPath = page.url.split(/[?#]/, 1)[0];

    const getTranslatedTitle = (item: NavItem) => {
        return t(item.title, item.count !== undefined ? { count: item.count } : undefined);
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarMenu>
                {items.map((item) => {
                    const href = typeof item.href === 'string' ? item.href : item.href.url;
                    const isActive = currentPath === href || (href !== '/' && currentPath.startsWith(`${href}/`));

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                isActive={isActive}
                                tooltip={{ children: getTranslatedTitle(item) }}
                                render={<MobileAwareLink href={item.href} prefetch aria-current={isActive ? 'page' : undefined} />}
                            >
                                {item.icon && <item.icon />}
                                <span>{getTranslatedTitle(item)}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
