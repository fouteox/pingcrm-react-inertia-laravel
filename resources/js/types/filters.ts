export type ContactsFilters = {
    search?: string;
    trashed?: 'with' | 'only';
};

export type OrganizationsFilters = {
    search?: string;
    trashed?: 'with' | 'only';
};

export type UsersFilters = {
    search?: string;
    role?: 'user' | 'owner';
    trashed?: 'with' | 'only';
};

export type FlashMessages = {
    success: string | null;
    error: string | null;
};
