import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, Organization, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChevronRight, Loader2, Trash } from 'lucide-react';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { Form, FormInput, FormLabel, FormMessage } from '@/components/form';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useDeletionControls } from '@/hooks/use-deletion-controls';

interface EditPageProps extends PageProps {
    organization: Organization;
}

export default function Edit() {
    const { t } = useTranslation();

    const { organization } = usePage<EditPageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Organization',
            count: 2,
            href: route('organizations.index'),
        },
        {
            title: organization.name,
            href: route('organizations.edit', organization.id),
        },
    ];

    const form = useForm({
        name: organization.name || '',
        email: organization.email || '',
        phone: organization.phone || '',
        address: organization.address || '',
        city: organization.city || '',
        region: organization.region || '',
        country: organization.country || '',
        postal_code: organization.postal_code || '',
    });

    function onSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.put(route('organizations.update', organization.id));
    }

    const handleDelete = async (id: number) => {
        form.delete(route('organizations.destroy', id));
    };

    const handleRestore = async (id: number) => {
        form.put(route('organizations.restore', id));
    };

    const { showDeleteControls } = useDeletionControls({
        resourceId: organization.id,
        isDeleted: !!organization.deleted_at,
        resourceType: 'organization',
        onDelete: handleDelete,
        onRestore: handleRestore,
        processing: form.processing,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={form.data.name} />

            {organization.deleted_at && showDeleteControls()}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit Organization')}</h2>

                <Form onSubmit={onSubmit}>
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="name" error={form.errors.name}>
                                    {t('Name')}
                                </FormLabel>

                                <FormInput
                                    id="name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    required
                                    autoFocus
                                    maxLength={100}
                                    disabled={form.processing}
                                    error={form.errors.name}
                                />

                                <FormMessage error={form.errors.name} />
                            </div>

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
                                    maxLength={50}
                                    disabled={form.processing}
                                    error={form.errors.email}
                                />

                                <FormMessage error={form.errors.email} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="phone" error={form.errors.phone}>
                                    {t('Phone')}
                                </FormLabel>

                                <FormInput
                                    id="phone"
                                    type="tel"
                                    value={form.data.phone}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                    maxLength={50}
                                    disabled={form.processing}
                                    error={form.errors.phone}
                                />

                                <FormMessage error={form.errors.phone} />
                            </div>

                            <div>
                                <FormLabel htmlFor="address" error={form.errors.address}>
                                    {t('Address')}
                                </FormLabel>

                                <FormInput
                                    id="address"
                                    type="text"
                                    value={form.data.address}
                                    onChange={(e) => form.setData('address', e.target.value)}
                                    maxLength={150}
                                    disabled={form.processing}
                                    error={form.errors.address}
                                />

                                <FormMessage error={form.errors.address} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="city" error={form.errors.city}>
                                    {t('City')}
                                </FormLabel>

                                <FormInput
                                    id="city"
                                    type="text"
                                    value={form.data.city}
                                    onChange={(e) => form.setData('city', e.target.value)}
                                    maxLength={50}
                                    disabled={form.processing}
                                    error={form.errors.city}
                                />

                                <FormMessage error={form.errors.city} />
                            </div>

                            <div>
                                <FormLabel htmlFor="region" error={form.errors.region}>
                                    {t('Province/State')}
                                </FormLabel>

                                <FormInput
                                    id="region"
                                    type="text"
                                    value={form.data.region}
                                    onChange={(e) => form.setData('region', e.target.value)}
                                    maxLength={50}
                                    disabled={form.processing}
                                    error={form.errors.region}
                                />

                                <FormMessage error={form.errors.region} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="country" error={form.errors.country}>
                                    {t('Country')}
                                </FormLabel>

                                <Select
                                    value={form.data.country || '0'}
                                    onValueChange={(value) => form.setData('country', value === '0' ? '' : value)}
                                    disabled={form.processing}
                                >
                                    <SelectTrigger id="country" className={form.errors.country ? 'border-destructive' : ''}>
                                        <SelectValue placeholder={t('None')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0">{t('None')}</SelectItem>
                                        <SelectItem value="CA">{t('Canada')}</SelectItem>
                                        <SelectItem value="US">{t('United States')}</SelectItem>
                                    </SelectContent>
                                </Select>

                                <FormMessage error={form.errors.country} />
                            </div>

                            <div>
                                <FormLabel htmlFor="postal_code" error={form.errors.postal_code}>
                                    {t('Postal Code')}
                                </FormLabel>

                                <FormInput
                                    id="postal_code"
                                    type="text"
                                    value={form.data.postal_code}
                                    onChange={(e) => form.setData('postal_code', e.target.value)}
                                    maxLength={25}
                                    disabled={form.processing}
                                    error={form.errors.postal_code}
                                />

                                <FormMessage error={form.errors.postal_code} />
                            </div>
                        </div>

                        <div className="flex justify-end gap-4">
                            {!organization.deleted_at && showDeleteControls()}

                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                {t('Update Organization')}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>

            <h2 className="mt-12 text-2xl font-bold">{t('Contact', { count: 2 })}</h2>
            <div className="mt-6 overflow-x-auto rounded-sm bg-white shadow-sm">
                <table className="w-full whitespace-nowrap">
                    <thead>
                        <tr className="text-left font-bold">
                            <th className="px-6 pt-5 pb-4">{t('Name')}</th>
                            <th className="px-6 pt-5 pb-4">{t('City')}</th>
                            <th className="px-6 pt-5 pb-4" colSpan={2}>
                                {t('Phone')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {organization.contacts.map(({ id, name, phone, city, deleted_at }) => {
                            return (
                                <tr key={id} className="focus-within:bg-gray-100 hover:bg-gray-100">
                                    <td className="border-t">
                                        <Link
                                            href={route('contacts.edit', id)}
                                            className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                            prefetch
                                        >
                                            {name}
                                            {deleted_at && <Trash className="ml-2 h-3 w-3 shrink-0 fill-current text-gray-400" />}
                                        </Link>
                                    </td>
                                    <td className="border-t">
                                        <Link
                                            tabIndex={-1}
                                            href={route('contacts.edit', id)}
                                            className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                            prefetch
                                        >
                                            {city}
                                        </Link>
                                    </td>
                                    <td className="border-t">
                                        <Link
                                            tabIndex={-1}
                                            href={route('contacts.edit', id)}
                                            className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                            prefetch
                                        >
                                            {phone}
                                        </Link>
                                    </td>
                                    <td className="w-px border-t">
                                        <Link tabIndex={-1} href={route('contacts.edit', id)} className="flex items-center px-4" prefetch>
                                            <ChevronRight className="block h-6 w-6 fill-current text-gray-400" />
                                        </Link>
                                    </td>
                                </tr>
                            );
                        })}
                        {organization.contacts.length === 0 && (
                            <tr>
                                <td className="border-t px-6 py-4" colSpan={4}>
                                    {t('No contacts found.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
