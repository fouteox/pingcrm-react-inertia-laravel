import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export interface BreadcrumbItem {
    title: string;
    count?: number;
    href: string;
}

export interface NavItem {
    title: string;
    count?: number;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}
