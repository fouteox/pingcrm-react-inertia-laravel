import React from 'react';
import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
    owner: string;
    deleted_at: string;
    account: Account;
    can_delete: boolean;
}

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

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
};

type DomElement =
    | HTMLElement
    | HTMLButtonElement
    | HTMLFormElement
    | HTMLInputElement;

type DomEvent<T extends DomElement = HTMLElement> =
    | React.MouseEvent<T>
    | React.FormEvent<T>;

export type FormAction<T extends DomElement = HTMLElement> = (
    e: DomEvent<T>,
) => void;
