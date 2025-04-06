import { MobileAwareLink } from '@/components/mobile-aware-link';
import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { t } = useTranslation();
    const page = usePage();

    const getTranslatedTitle = (item: NavItem) => {
        return t(item.title, item.count !== undefined ? { count: item.count } : undefined);
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={item.url === page.url}
                            tooltip={{ children: getTranslatedTitle(item) }}
                        >
                            <MobileAwareLink href={item.url} prefetch>
                                {item.icon && <item.icon />}
                                <span>{getTranslatedTitle(item)}</span>
                            </MobileAwareLink>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
