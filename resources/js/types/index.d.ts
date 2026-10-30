import type { Link } from '@inertiajs/svelte';
import type { LucideIcon } from '@lucide/svelte';
import type { ComponentProps } from 'svelte';
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
    href: NonNullable<ComponentProps<typeof Link>['href']>;
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
