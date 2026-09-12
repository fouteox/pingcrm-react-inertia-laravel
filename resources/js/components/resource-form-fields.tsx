import type { ComponentProps } from 'react';
import { useTranslation } from 'react-i18next';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface InputFieldProps extends ComponentProps<typeof Input> {
    id: string;
    label: string;
    error?: string;
}

export function InputField({ id, label, error, ...props }: InputFieldProps) {
    return (
        <Field data-invalid={!!error || undefined}>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <Input {...props} id={id} aria-invalid={!!error || undefined} aria-describedby={error ? `${id}-error` : undefined} />
            <FieldError id={`${id}-error`}>{error}</FieldError>
        </Field>
    );
}

interface SelectFieldProps {
    id: string;
    label: string;
    error?: string;
    value: string;
    onValueChange: (value: string) => void;
    items: { value: string; label: string }[];
    disabled?: boolean;
}

export function SelectField({ id, label, error, value, onValueChange, items, disabled }: SelectFieldProps) {
    return (
        <Field data-invalid={!!error || undefined}>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <Select name={id} items={items} value={value} onValueChange={(next) => onValueChange(next ?? '')} disabled={disabled}>
                <SelectTrigger id={id} className="w-full" aria-invalid={!!error || undefined} aria-describedby={error ? `${id}-error` : undefined}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {items.map((item) => (
                        <SelectItem key={item.value} value={item.value}>
                            {item.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <FieldError id={`${id}-error`}>{error}</FieldError>
        </Field>
    );
}

export type AddressFormData = {
    phone: string;
    address: string;
    city: string;
    region: string;
    country: string;
    postal_code: string;
};

interface AddressFieldsProps {
    data: AddressFormData;
    errors: Partial<Record<keyof AddressFormData, string>>;
    onChange: (field: keyof AddressFormData, value: string) => void;
    disabled: boolean;
}

export function AddressFields({ data, errors, onChange, disabled }: AddressFieldsProps) {
    const { t } = useTranslation();

    return (
        <>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <InputField
                    id="phone"
                    label={t('Phone')}
                    type="tel"
                    autoComplete="tel"
                    value={data.phone}
                    onChange={(event) => onChange('phone', event.target.value)}
                    error={errors.phone}
                    maxLength={50}
                    disabled={disabled}
                />
                <InputField
                    id="address"
                    label={t('Address')}
                    autoComplete="street-address"
                    value={data.address}
                    onChange={(event) => onChange('address', event.target.value)}
                    error={errors.address}
                    maxLength={150}
                    disabled={disabled}
                />
            </div>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <InputField
                    id="city"
                    label={t('City')}
                    autoComplete="address-level2"
                    value={data.city}
                    onChange={(event) => onChange('city', event.target.value)}
                    error={errors.city}
                    maxLength={50}
                    disabled={disabled}
                />
                <InputField
                    id="region"
                    label={t('Province/State')}
                    autoComplete="address-level1"
                    value={data.region}
                    onChange={(event) => onChange('region', event.target.value)}
                    error={errors.region}
                    maxLength={50}
                    disabled={disabled}
                />
            </div>
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <SelectField
                    id="country"
                    label={t('Country')}
                    value={data.country || '0'}
                    onValueChange={(value) => onChange('country', value === '0' ? '' : value)}
                    error={errors.country}
                    disabled={disabled}
                    items={[
                        { value: '0', label: t('None') },
                        { value: 'CA', label: t('Canada') },
                        { value: 'US', label: t('United States') },
                    ]}
                />
                <InputField
                    id="postal_code"
                    label={t('Postal Code')}
                    autoComplete="postal-code"
                    value={data.postal_code}
                    onChange={(event) => onChange('postal_code', event.target.value)}
                    error={errors.postal_code}
                    maxLength={25}
                    disabled={disabled}
                />
            </div>
        </>
    );
}
