import type { InertiaFormProps } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AddressFields, type AddressFormData, InputField, SelectField } from '@/components/resource-form-fields';
import type { UserOrganizationCollection } from '@/types/resources';

export type ContactFormData = AddressFormData & {
    first_name: string;
    last_name: string;
    organization_id: string;
    email: string;
};

export function ContactFormFields({ form, organizations }: { form: InertiaFormProps<ContactFormData>; organizations: UserOrganizationCollection }) {
    const { t } = useTranslation();
    const { data, errors, processing, setData } = form;

    return (
        <>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <InputField
                    id="first_name"
                    label={t('First name')}
                    autoComplete="given-name"
                    value={data.first_name}
                    onChange={(event) => setData('first_name', event.target.value)}
                    error={errors.first_name}
                    required
                    maxLength={25}
                    disabled={processing}
                />
                <InputField
                    id="last_name"
                    label={t('Last name')}
                    autoComplete="family-name"
                    value={data.last_name}
                    onChange={(event) => setData('last_name', event.target.value)}
                    error={errors.last_name}
                    required
                    maxLength={25}
                    disabled={processing}
                />
            </div>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <SelectField
                    id="organization_id"
                    label={t('Organization', { count: 1 })}
                    value={data.organization_id || '0'}
                    onValueChange={(value) => setData('organization_id', value === '0' ? '' : value)}
                    error={errors.organization_id}
                    disabled={processing}
                    items={[{ value: '0', label: t('None') }, ...organizations.map(({ id, name }) => ({ value: id.toString(), label: name }))]}
                />
                <InputField
                    id="email"
                    label={t('Email')}
                    type="email"
                    autoComplete="email"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                    maxLength={50}
                    disabled={processing}
                />
            </div>
            <AddressFields data={data} errors={errors} onChange={(field, value) => setData(field, value)} disabled={processing} />
        </>
    );
}
