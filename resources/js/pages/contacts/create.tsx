import { Button } from '@/components/ui/button';
import { BreadcrumbItem, Organization, SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import { Form, FormInput, FormLabel, FormMessage } from '@/components/form';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePageActions } from '@/contexts/page-context';

interface CreatePageProps extends SharedData {
    organizations: Organization[];
}

interface ContactFormData {
    first_name: string;
    last_name: string;
    organization_id: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
    [key: string]: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contact',
        count: 2,
        href: route('contacts.index'),
    },
    {
        title: 'Create',
        href: route('contacts.create'),
    },
];

export default function Create() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [setBreadcrumbs]);

    const { organizations } = usePage<CreatePageProps>().props;
    const form = useForm<ContactFormData>({
        first_name: '',
        last_name: '',
        organization_id: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        region: '',
        country: '',
        postal_code: '',
    });

    function onSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.post(route('contacts.store'));
    }

    return (
        <>
            <Head title={t('Create Contact')} />

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Create Contact')}</h2>

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
                                    autoFocus
                                    tabIndex={1}
                                    maxLength={25}
                                    disabled={form.processing}
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
                                    tabIndex={2}
                                    maxLength={25}
                                    disabled={form.processing}
                                    error={form.errors.last_name}
                                />

                                <FormMessage error={form.errors.last_name} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <FormLabel htmlFor="organization_id" error={form.errors.organization_id}>
                                    {t('Organization', { count: 1 })}
                                </FormLabel>

                                <Select
                                    value={form.data.organization_id || '0'}
                                    onValueChange={(value) => form.setData('organization_id', value === '0' ? '' : value)}
                                    disabled={form.processing}
                                >
                                    <SelectTrigger id="organization_id" className={form.errors.organization_id ? 'border-destructive' : ''}>
                                        <SelectValue placeholder={t('Select an organization')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0">{t('None')}</SelectItem>
                                        {organizations.map(({ id, name }) => (
                                            <SelectItem key={id} value={id.toString()}>
                                                {name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <FormMessage error={form.errors.organization_id} />
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
                                    tabIndex={4}
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
                                    tabIndex={5}
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
                                    tabIndex={6}
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
                                    tabIndex={7}
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
                                    tabIndex={8}
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
                                        <SelectValue placeholder={t('Select a country')} />
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
                                    tabIndex={10}
                                    maxLength={25}
                                    disabled={form.processing}
                                    error={form.errors.postal_code}
                                />

                                <FormMessage error={form.errors.postal_code} />
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                {t('Create Contact')}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>
        </>
    );
}
