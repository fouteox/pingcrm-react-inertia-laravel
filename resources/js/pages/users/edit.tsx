import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData, User } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { Form, FormInput, FormLabel, FormMessage } from '@/components/form';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useDeletionControls } from '@/hooks/use-deletion-controls';

interface EditPageProps extends SharedData {
    user: User & { password: string; photo: File | null };
}

export default function Edit() {
    const { t } = useTranslation();

    const { user } = usePage<EditPageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'User',
            count: 2,
            href: route('users.index'),
        },
        {
            title: `${user.first_name} ${user.last_name}`,
            href: route('users.edit', user.id),
        },
    ];

    const form = useForm({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        password: user.password || '',
        owner: user.owner ? '1' : '0',
        _method: 'PUT',
    });

    function onSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            password: data.password === '' ? undefined : data.password,
        }));

        form.post(route('users.update', user.id), {
            method: 'put',
        });
    }

    const handleDelete = async (id: number) => {
        form.delete(route('users.destroy', id));
    };

    const handleRestore = async (id: number) => {
        form.put(route('users.restore', id));
    };

    const { showDeleteControls } = useDeletionControls({
        resourceId: user.id,
        isDeleted: !!user.deleted_at,
        resourceType: 'user',
        canDelete: user.can_delete,
        onDelete: handleDelete,
        onRestore: handleRestore,
        processing: form.processing,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${form.data.first_name} ${form.data.last_name}`} />

            {!user.can_delete ? (
                <div className="mb-6 max-w-3xl rounded-sm border border-yellow-500 bg-yellow-400 p-4">
                    <div className="text-yellow-800">{t('Updating the demo user is not allowed.')}</div>
                </div>
            ) : (
                user.deleted_at && showDeleteControls()
            )}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit User')}</h2>

                <Form onSubmit={onSubmit}>
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="first_name" error={form.errors.first_name}>
                                    {t('First name')}
                                </FormLabel>

                                <FormInput
                                    id="first_name"
                                    type="text"
                                    value={form.data.first_name}
                                    onChange={(e) => form.setData('first_name', e.target.value)}
                                    required
                                    autoComplete="given-name"
                                    disabled={form.processing || !user.can_delete}
                                    error={form.errors.first_name}
                                />

                                <FormMessage error={form.errors.first_name} />
                            </div>

                            <div>
                                <FormLabel htmlFor="last_name" error={form.errors.last_name}>
                                    {t('Last name')}
                                </FormLabel>

                                <FormInput
                                    id="last_name"
                                    type="text"
                                    value={form.data.last_name}
                                    onChange={(e) => form.setData('last_name', e.target.value)}
                                    required
                                    autoComplete="family-name"
                                    disabled={form.processing || !user.can_delete}
                                    error={form.errors.last_name}
                                />

                                <FormMessage error={form.errors.last_name} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="email" error={form.errors.email}>
                                    {t('Email')}
                                </FormLabel>

                                <FormInput
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    required
                                    autoComplete="email"
                                    disabled={form.processing || !user.can_delete}
                                    error={form.errors.email}
                                />

                                <FormMessage error={form.errors.email} />
                            </div>

                            <div>
                                <FormLabel htmlFor="password" error={form.errors.password}>
                                    {t('Password')}
                                </FormLabel>

                                <FormInput
                                    id="password"
                                    type="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    autoComplete="new-password"
                                    disabled={form.processing || !user.can_delete}
                                    error={form.errors.password}
                                />

                                <FormMessage error={form.errors.password} />
                            </div>
                        </div>

                        <div>
                            <FormLabel htmlFor="owner" error={form.errors.owner}>
                                {t('Owner')}
                            </FormLabel>

                            <Select
                                value={form.data.owner}
                                onValueChange={(value) => form.setData('owner', value)}
                                disabled={form.processing || !user.can_delete}
                            >
                                <SelectTrigger id="owner" className={form.errors.owner ? 'border-destructive' : ''}>
                                    <SelectValue placeholder={t('Select an option')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">{t('Yes')}</SelectItem>
                                    <SelectItem value="0">{t('No')}</SelectItem>
                                </SelectContent>
                            </Select>

                            <FormMessage error={form.errors.owner} />
                        </div>

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!user.deleted_at && user.can_delete && showDeleteControls()}

                            <Button type="submit" disabled={form.processing || !user.can_delete}>
                                {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                {t('Update User')}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>
        </AppLayout>
    );
}
