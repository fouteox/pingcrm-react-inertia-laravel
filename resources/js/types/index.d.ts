import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { Inertia } from '@/wayfinder/types';
import type { UserResource } from './resources';

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
}

export type SharedData = Omit<Inertia.SharedData, 'auth'> & {
    auth: { user: UserResource | null };
    sidebarOpen: boolean;
    reverb: {
        key: string;
        host: string;
        port: number;
        scheme: string;
    };
};
