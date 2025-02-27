import { Button } from '@/components/ui/button';
import { BreadcrumbItem, Organization, SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChevronRight, Loader2, Trash } from 'lucide-react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import { Form, FormInput, FormLabel, FormMessage } from '@/components/form';
import { TableContainer } from '@/components/table-container';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePageActions } from '@/contexts/page-context';
import { useDeletionControls } from '@/hooks/use-deletion-controls';

interface EditPageProps extends SharedData {
    organization: Organization;
}

export default function Edit() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const { organization } = usePage<EditPageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Organization',
                count: 2,
                href: route('organizations.index'),
            },
            {
                title: organization.name,
                href: route('organizations.edit', organization.id),
            },
        ],
        [organization.name, organization.id],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

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
        <>
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

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!organization.deleted_at && showDeleteControls()}

                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                {t('Update Organization')}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>

            <h2 className="mt-12 mb-6 text-lg font-semibold">{t('Contact', { count: 2 })}</h2>

            <TableContainer>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Name')}</TableHead>
                        <TableHead>{t('City')}</TableHead>
                        <TableHead colSpan={2}>{t('Phone')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {organization.contacts.map(({ id, name, phone, city, deleted_at }) => {
                        return (
                            <TableRow key={id}>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={route('contacts.edit', id)} prefetch className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
                                        {name}
                                        {deleted_at && <Trash className="text-muted-foreground ml-2 h-3 w-3 shrink-0" />}
                                    </div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={route('contacts.edit', id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{city}</div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={route('contacts.edit', id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{phone}</div>
                                </TableCell>
                                <TableCell className="w-px">
                                    <Button asChild variant="ghost" size="icon">
                                        <Link tabIndex={-1} href={route('contacts.edit', id)} prefetch>
                                            <ChevronRight className="text-muted-foreground h-4 w-4" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        );
                    })}
                    {organization.contacts.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={4} className="h-24 text-center">
                                {t('No contacts found.')}
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </TableContainer>
        </>
    );
}
