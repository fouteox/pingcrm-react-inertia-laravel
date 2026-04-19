import type { App } from '@/wayfinder/types';

type PaginatedResource<T> = {
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

export type UserResource = {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    owner: boolean;
    deleted_at: string | null;
    account?: App.Models.Account;
    can_delete: boolean;
};

export type UserCollection = PaginatedResource<{
    id: number;
    name: string;
    email: string;
    owner: boolean;
    deleted_at: string | null;
}>;

export type OrganizationContactResource = {
    id: number;
    name: string;
    city: string | null;
    phone: string | null;
    deleted_at: string | null;
};

export type OrganizationResource = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    postal_code: string | null;
    deleted_at: string | null;
    contacts: OrganizationContactResource[];
};

export type OrganizationCollection = PaginatedResource<{
    id: number;
    name: string;
    phone: string | null;
    city: string | null;
    deleted_at: string | null;
}>;

export type ContactResource = {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    postal_code: string | null;
    deleted_at: string | null;
    organization_id: number | null;
};

export type ContactCollection = PaginatedResource<{
    id: number;
    name: string;
    phone: string | null;
    city: string | null;
    deleted_at: string | null;
    organization: { id: number; name: string } | null;
}>;

export type UserOrganizationCollection = Array<{
    id: number;
    name: string;
}>;
