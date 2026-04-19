import { Head, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React, { FormEvent, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePageActions } from '@/contexts/page-context';
import { BreadcrumbItem } from '@/types';
import { store } from '@/wayfinder/App/Http/Controllers/UsersController';
import users from '@/wayfinder/routes/users';

type UserForm = {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    owner: string;
};

export default function Create() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Users',
                count: 2,
                href: users.index().url,
            },
            {
                title: 'Create',
                href: users.create().url,
            },
        ],
        [],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    const form = useForm<UserForm>({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        owner: '0',
    });

    const ownerItems = React.useMemo(
        () => [
            { value: '1', label: t('Yes') },
            { value: '0', label: t('No') },
        ],
        [t],
    );

    const errors = form.processing ? ({} as typeof form.errors) : form.errors;

    function onSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <>
            <Head title={t('Create User')} />

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Create User')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Field data-invalid={!!errors.first_name || undefined}>
                                <FieldLabel htmlFor="first_name">{t('First name')}</FieldLabel>
                                <Input
                                    id="first_name"
                                    type="text"
                                    value={form.data.first_name}
                                    onChange={(e) => form.setData('first_name', e.target.value)}
                                    required
                                    autoComplete="given-name"
                                    disabled={form.processing}
                                    aria-invalid={!!errors.first_name || undefined}
                                />
                                <FieldError>{errors.first_name}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.last_name || undefined}>
                                <FieldLabel htmlFor="last_name">{t('Last name')}</FieldLabel>
                                <Input
                                    id="last_name"
                                    type="text"
                                    value={form.data.last_name}
                                    onChange={(e) => form.setData('last_name', e.target.value)}
                                    required
                                    autoComplete="family-name"
                                    disabled={form.processing}
                                    aria-invalid={!!errors.last_name || undefined}
                                />
                                <FieldError>{errors.last_name}</FieldError>
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Field data-invalid={!!errors.email || undefined}>
                                <FieldLabel htmlFor="email">{t('Email')}</FieldLabel>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    required
                                    autoComplete="email"
                                    disabled={form.processing}
                                    aria-invalid={!!errors.email || undefined}
                                />
                                <FieldError>{errors.email}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.password || undefined}>
                                <FieldLabel htmlFor="password">{t('Password')}</FieldLabel>
                                <Input
                                    id="password"
                                    type="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    required
                                    autoComplete="new-password"
                                    disabled={form.processing}
                                    aria-invalid={!!errors.password || undefined}
                                />
                                <FieldError>{errors.password}</FieldError>
                            </Field>
                        </div>

                        <Field data-invalid={!!errors.owner || undefined}>
                            <FieldLabel htmlFor="owner">{t('Owner')}</FieldLabel>
                            <Select
                                items={ownerItems}
                                value={form.data.owner}
                                onValueChange={(value) => form.setData('owner', value ?? '')}
                                disabled={form.processing}
                            >
                                <SelectTrigger id="owner" className="w-full" aria-invalid={!!errors.owner || undefined}>
                                    <SelectValue placeholder={t('Select an option')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {ownerItems.map(({ value, label }) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <FieldError>{errors.owner}</FieldError>
                        </Field>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Loader2 className="mr-2 size-4 animate-spin" />}
                                {t('Create User')}
                            </Button>
                        </div>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}
