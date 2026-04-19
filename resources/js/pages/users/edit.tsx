import { Head, useForm } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { SubmitButton } from '@/components/submit-button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePageActions } from '@/contexts/page-context';
import { useAppPage } from '@/hooks/use-app-page';
import { useDeletionControls } from '@/hooks/use-deletion-controls';
import { useFormProcessing } from '@/hooks/use-form-processing';
import { BreadcrumbItem } from '@/types';
import type { UserResource } from '@/types/resources';
import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/UsersController';
import users from '@/wayfinder/routes/users';

type EditPageProps = {
    user: UserResource;
};

type UserForm = {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    owner: string;
};

export default function Edit() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const { user } = useAppPage<EditPageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'User',
                count: 2,
                href: users.index().url,
            },
            {
                title: `${user.first_name} ${user.last_name}`,
                href: users.edit(user.id).url,
            },
        ],
        [user.first_name, user.last_name, user.id],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    const form = useForm<UserForm>({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        password: '',
        owner: user.owner ? '1' : '0',
    });

    const ownerItems = React.useMemo(
        () => [
            { value: '1', label: t('Yes') },
            { value: '0', label: t('No') },
        ],
        [t],
    );

    const isProcessing = useFormProcessing(form.processing);
    const errors = isProcessing ? ({} as typeof form.errors) : form.errors;
    const isDisabled = isProcessing || !user.can_delete;

    function onSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            password: data.password === '' ? undefined : data.password,
        }));

        form.submit(update(user), {
            preserveScroll: true,
        });
    }

    const { showDeleteControls } = useDeletionControls({
        isDeleted: !!user.deleted_at,
        resourceType: 'user',
        canDelete: user.can_delete,
        deleteAction: destroy(user),
        restoreAction: restore(user),
    });

    return (
        <>
            <Head title={`${form.data.first_name} ${form.data.last_name}`} />

            {!user.can_delete ? (
                <Alert className="mb-6 max-w-3xl items-center border-yellow-500 bg-yellow-100 text-yellow-800 dark:border-yellow-600/30 dark:bg-yellow-600/10 dark:text-yellow-500">
                    <AlertDescription className="flex w-full items-center justify-between text-yellow-700 dark:text-yellow-500/90">
                        {t('Updating the demo user is not allowed.')}
                    </AlertDescription>
                </Alert>
            ) : (
                user.deleted_at && showDeleteControls()
            )}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit User')}</h2>

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
                                    disabled={isDisabled}
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
                                    disabled={isDisabled}
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
                                    disabled={isDisabled}
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
                                    autoComplete="new-password"
                                    disabled={isDisabled}
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
                                disabled={isDisabled}
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

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!user.deleted_at && user.can_delete && showDeleteControls()}

                            <SubmitButton processing={isProcessing} disabled={!user.can_delete}>
                                {t('Update User')}
                            </SubmitButton>
                        </div>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}
