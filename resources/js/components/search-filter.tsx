import { router } from '@inertiajs/react';
import { Filter, X } from 'lucide-react';
import * as React from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useAppPage } from '@/hooks/use-app-page';

interface Values {
    role: string;
    search: string;
    trashed: string;
}

// Use constants for special values
const ANY_VALUE = 'any'; // Instead of empty string

function pickBy(object: Values): Partial<Values> {
    const keys: Array<keyof Values> = ['role', 'search', 'trashed'];
    return keys.reduce<Partial<Values>>((acc, key) => {
        const value = object[key];
        // If the value is ANY_VALUE, don't include it in the query
        if (value !== '' && value !== undefined && value !== null && value !== ANY_VALUE) {
            acc[key] = value;
        }
        return acc;
    }, {});
}

type SearchFilterPageProps = {
    filters: {
        role?: string;
        search?: string;
        trashed?: string;
    };
};

export default function SearchFilter() {
    const { t } = useTranslation();
    const { filters } = useAppPage<SearchFilterPageProps>().props;
    const [open, setOpen] = React.useState(false);

    const [values, setValues] = React.useState<Values>({
        // Convert empty strings in filters to ANY_VALUE
        role: filters.role || ANY_VALUE,
        search: filters.search || '',
        trashed: filters.trashed || ANY_VALUE,
    });

    const roleItems = React.useMemo(
        () => [
            { value: ANY_VALUE, label: t('Any') },
            { value: 'user', label: t('User') },
            { value: 'owner', label: t('Owner') },
        ],
        [t],
    );

    const trashedItems = React.useMemo(
        () => [
            { value: ANY_VALUE, label: t('Any') },
            { value: 'with', label: t('With Trashed') },
            { value: 'only', label: t('Only Trashed') },
        ],
        [t],
    );

    function handleChange(key: keyof Values, value: string) {
        const newValues = {
            ...values,
            [key]: value,
        };

        setValues(newValues);

        const query = pickBy(newValues);

        router.get(window.location.pathname, query, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <ButtonGroup className="w-64">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger render={<Button variant="outline" size="icon" aria-label={t('Filter')} />}>
                    <Filter />
                </PopoverTrigger>
                <PopoverContent className="w-auto min-w-72" align="start">
                    <div className="grid gap-4">
                        {Object.prototype.hasOwnProperty.call(filters, 'role') && (
                            <div className="grid gap-2">
                                <Label htmlFor="role">{t('Role')}</Label>
                                <Select items={roleItems} value={values.role} onValueChange={(value) => handleChange('role', value ?? '')}>
                                    <SelectTrigger id="role" className="w-full">
                                        <SelectValue placeholder={t('Select role')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roleItems.map(({ value, label }) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor="trashed">{t('Trashed')}</Label>
                            <Select items={trashedItems} value={values.trashed} onValueChange={(value) => handleChange('trashed', value ?? '')}>
                                <SelectTrigger id="trashed" className="w-full">
                                    <SelectValue placeholder={t('Select trashed status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {trashedItems.map(({ value, label }) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>
            <InputGroup>
                <InputGroupInput
                    type="text"
                    placeholder={t('Search')}
                    value={values.search}
                    onChange={(e) => handleChange('search', e.target.value)}
                />
                {values.search && (
                    <InputGroupAddon align="inline-end">
                        <InputGroupButton size="icon-sm" aria-label={t('Clear')} onClick={() => handleChange('search', '')}>
                            <X />
                        </InputGroupButton>
                    </InputGroupAddon>
                )}
            </InputGroup>
        </ButtonGroup>
    );
}
