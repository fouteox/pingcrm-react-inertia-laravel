import type { InertiaFormProps } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AddressFields, type AddressFormData, InputField } from '@/components/resource-form-fields';

export type OrganizationFormData = AddressFormData & {
    name: string;
    email: string;
};

export function OrganizationFormFields({ form }: { form: InertiaFormProps<OrganizationFormData> }) {
    const { t } = useTranslation();
    const { data, errors, processing, setData } = form;

    return (
        <>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <InputField
                    id="name"
                    label={t('Name')}
                    autoComplete="organization"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    error={errors.name}
                    required
                    maxLength={100}
                    disabled={processing}
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
