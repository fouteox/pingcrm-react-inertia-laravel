import type { InertiaFormProps } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { InputField, SelectField } from '@/components/resource-form-fields';

export type UserFormData = {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    owner: string;
};

export function UserFormFields({
    form,
    creating = false,
    disabled = false,
}: {
    form: InertiaFormProps<UserFormData>;
    creating?: boolean;
    disabled?: boolean;
}) {
    const { t } = useTranslation();
    const { data, errors, setData } = form;
    const isDisabled = disabled || form.processing;

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
                    disabled={isDisabled}
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
                    disabled={isDisabled}
                />
            </div>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <InputField
                    id="email"
                    label={t('Email')}
                    type="email"
                    autoComplete="email"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                    required
                    maxLength={50}
                    disabled={isDisabled}
                />
                <InputField
                    id="password"
                    label={t('Password')}
                    type="password"
                    autoComplete="new-password"
                    value={data.password}
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                    required={creating}
                    disabled={isDisabled}
                />
            </div>
            <SelectField
                id="owner"
                label={t('Owner')}
                value={data.owner}
                onValueChange={(value) => setData('owner', value)}
                error={errors.owner}
                disabled={isDisabled}
                items={[
                    { value: '1', label: t('Yes') },
                    { value: '0', label: t('No') },
                ]}
            />
        </>
    );
}
