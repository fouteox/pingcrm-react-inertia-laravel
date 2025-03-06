import { LucideIcon } from 'lucide-react';
import { Config } from 'ziggy-js';

export interface BreadcrumbItem {
    title: string;
    count?: number;
    href: string;
}

export interface NavItem {
    title: string;
    count?: number;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface User {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
    avatar?: string;
    owner: string;
    deleted_at: string;
    account: Account;
    can_delete: boolean;
    [key: string]: unknown; // This allows for additional properties...
}

export type UserFormData = {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    owner: string;
    [key: string]: string;
};

export interface Organization {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
    deleted_at: string;
    contacts: Contact[];
}

export type OrganizationFormData = {
    name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
    [key: string]: string;
};

export interface Contact {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
    deleted_at: string;
    organization_id: number;
    organization: Organization;
}

export type ContactFormData = {
    first_name: string;
    last_name: string;
    organization_id: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
    [key: string]: string;
};

export type PaginatedData<T> = {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };

    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;

        links: {
            url: null | string;
            label: string;
            active: boolean;
        }[];
    };
};

interface TranslationStore {
    [locale: string]: {
        translation: Record<string, string>;
    };
}

export interface SharedData {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
    locale: string;
    translations: TranslationStore | null;
    [key: string]: unknown;
}
