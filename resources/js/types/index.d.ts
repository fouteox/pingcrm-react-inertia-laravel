import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { Inertia } from '@/wayfinder/types';
import type { FlashMessages } from './filters';

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

export type SharedData = Omit<Inertia.SharedData, 'flash'> & {
    flash: FlashMessages;
};
