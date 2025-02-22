import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Layout from '@/Components/Layout';
import LoadingButton from '@/Components/LoadingButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { Organization, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface CreatePageProps extends PageProps {
    organizations: Organization[];
}

const Create = () => {
    const { t } = useTranslation();

    const { organizations } = usePage<CreatePageProps>().props;
    const { data, setData, errors, post, processing } = useForm({
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

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post(route('contacts.store'));
    }

    return (
        <>
            <Head title={t('Create Contact')} />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route('contacts.index')}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    {t('Contact', { count: 2 })}
                </Link>
                <span className="font-medium text-indigo-600"> /</span>{' '}
                {t('Create')}
            </h1>
            <div className="max-w-3xl overflow-hidden rounded-sm bg-white shadow-sm">
                <form onSubmit={handleSubmit}>
                    <div className="-mr-6 -mb-8 flex flex-wrap p-8">
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="first_name"
                                value={t('First name')}
                            />
                            <TextInput
                                name="first_name"
                                value={data.first_name}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData('first_name', e.target.value)
                                }
                            />
                            <InputError message={errors.first_name} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="last_name"
                                value={t('Last name')}
                            />
                            <TextInput
                                name="last_name"
                                value={data.last_name}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData('last_name', e.target.value)
                                }
                            />
                            <InputError message={errors.last_name} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="organization_id"
                                value={t('Organization')}
                            />
                            <SelectInput
                                name="organization_id"
                                value={data.organization_id}
                                onChange={(e) =>
                                    setData('organization_id', e.target.value)
                                }
                            >
                                <option value=""></option>
                                {organizations.map(({ id, name }) => (
                                    <option key={id} value={id}>
                                        {name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel forInput="email" value={t('Email')} />
                            <TextInput
                                name="email"
                                type="email"
                                value={data.email}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('email', e.target.value)
                                }
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel forInput="phone" value={t('Phone')} />
                            <TextInput
                                name="phone"
                                value={data.phone}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                            />
                            <InputError message={errors.phone} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="address"
                                value={t('Address')}
                            />
                            <TextInput
                                name="address"
                                value={data.address}
                                maxLength={150}
                                handleChange={(e) =>
                                    setData('address', e.target.value)
                                }
                            />
                            <InputError message={errors.address} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel forInput="city" value={t('City')} />
                            <TextInput
                                name="city"
                                value={data.city}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('city', e.target.value)
                                }
                            />
                            <InputError message={errors.city} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="region"
                                value={t('Province/State')}
                            />
                            <TextInput
                                name="region"
                                value={data.region}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('region', e.target.value)
                                }
                            />
                            <InputError message={errors.region} />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="country"
                                value={t('Country')}
                            />
                            <SelectInput
                                name="country"
                                value={data.country}
                                onChange={(e) =>
                                    setData('country', e.target.value)
                                }
                            >
                                <option value=""></option>
                                <option value="CA">{t('Canada')}</option>
                                <option value="US">{t('United States')}</option>
                            </SelectInput>
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="postal_code"
                                value={t('Postal Code')}
                            />
                            <TextInput
                                name="postal_code"
                                value={data.postal_code}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData('postal_code', e.target.value)
                                }
                            />
                            <InputError message={errors.postal_code} />
                        </div>
                    </div>
                    <div className="flex items-center justify-end border-t border-gray-200 bg-gray-100 px-8 py-4">
                        <LoadingButton
                            processing={processing}
                            type="submit"
                            className="btn-indigo"
                        >
                            {t('Create Contact')}
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Create.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Create;
