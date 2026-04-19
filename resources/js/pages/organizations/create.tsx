import { Head, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePageActions } from '@/contexts/page-context';
import { BreadcrumbItem } from '@/types';
import { store } from '@/wayfinder/App/Http/Controllers/OrganizationsController';
import organizations from '@/wayfinder/routes/organizations';

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

export default function Create() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Organization',
                count: 2,
                href: organizations.index().url,
            },
            {
                title: 'Create',
                href: organizations.create().url,
            },
        ],
        [],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    const countryItems = React.useMemo(
        () => [
            { value: '0', label: t('None') },
            { value: 'CA', label: t('Canada') },
            { value: 'US', label: t('United States') },
        ],
        [t],
    );

    const form = useForm<OrganizationForm>({
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        region: '',
        country: '',
        postal_code: '',
    });

    const errors = form.processing ? ({} as typeof form.errors) : form.errors;

    function onSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <>
            <Head title={t('Create Organization')} />

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Create Organization')}</h2>

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
                                    disabled={form.processing}
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
                                    disabled={form.processing}
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
                                    disabled={form.processing}
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
                                    disabled={form.processing}
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
                                    disabled={form.processing}
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
                                    disabled={form.processing}
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
                                    disabled={form.processing}
                                >
                                    <SelectTrigger id="country" className="w-full" aria-invalid={!!errors.country || undefined}>
                                        <SelectValue placeholder={t('Select a country')} />
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
                                    disabled={form.processing}
                                    aria-invalid={!!errors.postal_code || undefined}
                                />
                                <FieldError>{errors.postal_code}</FieldError>
                            </Field>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Loader2 className="mr-2 size-4 animate-spin" />}
                                {t('Create Organization')}
                            </Button>
                        </div>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}
