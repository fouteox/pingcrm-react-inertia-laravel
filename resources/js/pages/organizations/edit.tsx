import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronRight, Trash } from 'lucide-react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { SubmitButton } from '@/components/submit-button';
import { TableContainer } from '@/components/table-container';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePageActions } from '@/contexts/page-context';
import { useAppPage } from '@/hooks/use-app-page';
import { useDeletionControls } from '@/hooks/use-deletion-controls';
import { useFormProcessing } from '@/hooks/use-form-processing';
import { BreadcrumbItem } from '@/types';
import type { OrganizationResource } from '@/types/resources';
import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/OrganizationsController';
import contacts from '@/wayfinder/routes/contacts';
import organizations from '@/wayfinder/routes/organizations';

type EditPageProps = {
    organization: OrganizationResource;
};

type OrganizationForm = {
    name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
};

export default function Edit() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const { organization } = useAppPage<EditPageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Organization',
                count: 2,
                href: organizations.index().url,
            },
            {
                title: organization.name,
                href: organizations.edit(organization.id).url,
            },
        ],
        [organization.name, organization.id],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    const form = useForm<OrganizationForm>({
        name: organization.name || '',
        email: organization.email || '',
        phone: organization.phone || '',
        address: organization.address || '',
        city: organization.city || '',
        region: organization.region || '',
        country: organization.country || '',
        postal_code: organization.postal_code || '',
    });

    const countryItems = React.useMemo(
        () => [
            { value: '0', label: t('None') },
            { value: 'CA', label: t('Canada') },
            { value: 'US', label: t('United States') },
        ],
        [t],
    );

    const isProcessing = useFormProcessing(form.processing);
    const errors = isProcessing ? ({} as typeof form.errors) : form.errors;

    function onSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(update(organization), {
            preserveScroll: true,
        });
    }

    const { showDeleteControls } = useDeletionControls({
        isDeleted: !!organization.deleted_at,
        resourceType: 'organization',
        deleteAction: destroy(organization),
        restoreAction: restore(organization),
    });

    return (
        <>
            <Head title={form.data.name} />

            {organization.deleted_at && showDeleteControls()}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit Organization')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Field data-invalid={!!errors.name || undefined}>
                                <FieldLabel htmlFor="name">{t('Name')}</FieldLabel>
                                <Input
                                    id="name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    required
                                    autoFocus
                                    maxLength={100}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.name || undefined}
                                />
                                <FieldError>{errors.name}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.email || undefined}>
                                <FieldLabel htmlFor="email">{t('Email')}</FieldLabel>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    required
                                    maxLength={50}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.email || undefined}
                                />
                                <FieldError>{errors.email}</FieldError>
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Field data-invalid={!!errors.phone || undefined}>
                                <FieldLabel htmlFor="phone">{t('Phone')}</FieldLabel>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={form.data.phone}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                    maxLength={50}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.phone || undefined}
                                />
                                <FieldError>{errors.phone}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.address || undefined}>
                                <FieldLabel htmlFor="address">{t('Address')}</FieldLabel>
                                <Input
                                    id="address"
                                    type="text"
                                    value={form.data.address}
                                    onChange={(e) => form.setData('address', e.target.value)}
                                    maxLength={150}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.address || undefined}
                                />
                                <FieldError>{errors.address}</FieldError>
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Field data-invalid={!!errors.city || undefined}>
                                <FieldLabel htmlFor="city">{t('City')}</FieldLabel>
                                <Input
                                    id="city"
                                    type="text"
                                    value={form.data.city}
                                    onChange={(e) => form.setData('city', e.target.value)}
                                    maxLength={50}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.city || undefined}
                                />
                                <FieldError>{errors.city}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.region || undefined}>
                                <FieldLabel htmlFor="region">{t('Province/State')}</FieldLabel>
                                <Input
                                    id="region"
                                    type="text"
                                    value={form.data.region}
                                    onChange={(e) => form.setData('region', e.target.value)}
                                    maxLength={50}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.region || undefined}
                                />
                                <FieldError>{errors.region}</FieldError>
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <Field data-invalid={!!errors.country || undefined}>
                                <FieldLabel htmlFor="country">{t('Country')}</FieldLabel>
                                <Select
                                    items={countryItems}
                                    value={form.data.country || '0'}
                                    onValueChange={(value) => form.setData('country', !value || value === '0' ? '' : value)}
                                    disabled={isProcessing}
                                >
                                    <SelectTrigger id="country" className="w-full" aria-invalid={!!errors.country || undefined}>
                                        <SelectValue placeholder={t('None')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countryItems.map(({ value, label }) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <FieldError>{errors.country}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.postal_code || undefined}>
                                <FieldLabel htmlFor="postal_code">{t('Postal Code')}</FieldLabel>
                                <Input
                                    id="postal_code"
                                    type="text"
                                    value={form.data.postal_code}
                                    onChange={(e) => form.setData('postal_code', e.target.value)}
                                    maxLength={25}
                                    disabled={isProcessing}
                                    aria-invalid={!!errors.postal_code || undefined}
                                />
                                <FieldError>{errors.postal_code}</FieldError>
                            </Field>
                        </div>

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!organization.deleted_at && showDeleteControls()}

                            <SubmitButton processing={isProcessing}>{t('Update Organization')}</SubmitButton>
                        </div>
                    </FieldGroup>
                </form>
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
                                        <Link href={contacts.edit(id)} prefetch className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
                                        {name}
                                        {deleted_at && <Trash className="ml-2 size-3 shrink-0 text-muted-foreground" />}
                                    </div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{city}</div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{phone}</div>
                                </TableCell>
                                <TableCell className="w-px">
                                    <Button
                                        render={<Link tabIndex={-1} href={contacts.edit(id)} prefetch />}
                                        nativeButton={false}
                                        variant="ghost"
                                        size="icon"
                                    >
                                        <ChevronRight className="size-4 text-muted-foreground" />
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
