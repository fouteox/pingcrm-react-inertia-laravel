import SelectInput from '@/Components/SelectInput';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { ChangeEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Values {
    role: string;
    search: string;
    trashed: string;
}

function pickBy(object: Values): Partial<Values> {
    const keys: Array<keyof Values> = ['role', 'search', 'trashed'];
    return keys.reduce<Partial<Values>>((acc, key) => {
        const value = object[key];
        if (value !== '' && value !== undefined && value !== null) {
            acc[key] = value;
        }
        return acc;
    }, {});
}

interface SearchFilterPageProps extends PageProps {
    filters: {
        role?: string;
        search?: string;
        trashed?: string;
    };
}

export default function SearchFilter() {
    const { t } = useTranslation();

    const { filters } = usePage<SearchFilterPageProps>().props;
    const [opened, setOpened] = useState(false);

    const [values, setValues] = useState<Values>({
        role: filters.role || '',
        search: filters.search || '',
        trashed: filters.trashed || '',
    });

    function reset() {
        const areValuesEmpty =
            !values.role && !values.search && !values.trashed;

        if (areValuesEmpty) {
            return;
        }

        setValues({
            role: '',
            search: '',
            trashed: '',
        });

        router.get(
            route(route().current() as string),
            {},
            {
                replace: true,
                preserveState: true,
            },
        );
    }

    function handleChange(
        e: ChangeEvent<HTMLInputElement | HTMLSelectElement>,
    ) {
        const key = e.target.name;
        const value = e.target.value;

        const newValues = {
            ...values,
            [key]: value,
        };

        setValues(newValues);

        const query = pickBy(newValues);

        router.get(route(route().current() as string), query, {
            replace: true,
            preserveState: true,
        });

        if (opened) setOpened(false);
    }

    return (
        <div className="mr-4 flex w-full max-w-md items-center">
            <div className="relative flex w-full rounded-sm bg-white shadow-sm">
                <div
                    style={{ top: '100%' }}
                    className={`absolute ${opened ? '' : 'hidden'}`}
                >
                    <div
                        onClick={() => setOpened(false)}
                        className="fixed inset-0 z-20 bg-black opacity-25"
                    ></div>
                    <div className="relative z-30 mt-2 w-64 rounded-sm bg-white px-4 py-6 shadow-lg">
                        {Object.prototype.hasOwnProperty.call(
                            filters,
                            'role',
                        ) && (
                            <SelectInput
                                className="mb-4"
                                label="Role"
                                name="role"
                                value={values.role}
                                onChange={handleChange}
                            >
                                <option value=""></option>
                                <option value="user">{t('User')}</option>
                                <option value="owner">{t('Owner')}</option>
                            </SelectInput>
                        )}
                        <SelectInput
                            label="Trashed"
                            name="trashed"
                            value={values.trashed}
                            onChange={handleChange}
                        >
                            <option value=""></option>
                            <option value="with">{t('With Trashed')}</option>
                            <option value="only">{t('Only Trashed')}</option>
                        </SelectInput>
                    </div>
                </div>
                <button
                    onClick={() => setOpened(true)}
                    className="rounded-l border-r px-4 hover:bg-gray-100 focus:z-10 focus:border-white focus:ring-3 focus:ring-indigo-400/50 focus:outline-hidden md:px-6"
                >
                    <div className="flex items-baseline">
                        <span className="hidden text-gray-700 md:inline">
                            {t('Filter')}
                        </span>
                        <svg
                            className="h-2 w-2 fill-current text-gray-700 md:ml-2"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 961.243 599.998"
                        >
                            <path d="M239.998 239.999L0 0h961.243L721.246 240c-131.999 132-240.28 240-240.624 239.999-.345-.001-108.625-108.001-240.624-240z" />
                        </svg>
                    </div>
                </button>
                <input
                    className="focus:shadow-outline relative w-full rounded-r border-none px-6 py-2 focus:ring-2"
                    autoComplete="off"
                    type="text"
                    name="search"
                    value={values.search}
                    onChange={handleChange}
                    placeholder={t('Search')}
                />
            </div>
            <button
                onClick={reset}
                className="ml-3 text-sm text-gray-600 hover:text-gray-700 focus:text-indigo-700 focus:outline-hidden"
                type="button"
            >
                {t('Reset')}
            </button>
        </div>
    );
}
