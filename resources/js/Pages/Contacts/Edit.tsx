import { Field } from '@/Components/Form/Field';
import Layout from '@/Components/Layout';
import LoadingButton from '@/Components/LoadingButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { useDeletionControls } from '@/hooks/useDeletionControls';
import { Contact, Organization, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface EditPageProps extends PageProps {
    contact: Contact;
    organizations: Organization[];
}

const Edit = () => {
    const { t } = useTranslation();

    const { contact, organizations } = usePage<EditPageProps>().props;
    const {
        data,
        setData,
        errors,
        put,
        processing,
        delete: destroy,
    } = useForm({
        first_name: contact.first_name || '',
        last_name: contact.last_name || '',
        organization_id: contact.organization_id || '',
        email: contact.email || '',
        phone: contact.phone || '',
        address: contact.address || '',
        city: contact.city || '',
        region: contact.region || '',
        country: contact.country || '',
        postal_code: contact.postal_code || '',
    });

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();

        put(route('contacts.update', contact.id));
    }

    const handleDelete = async (id: number) => {
        destroy(route('contacts.destroy', id));
    };

    const handleRestore = async (id: number) => {
        put(route('contacts.restore', id));
    };

    const { showDeleteControls } = useDeletionControls({
        resourceId: contact.id,
        isDeleted: !!contact.deleted_at,
        resourceType: 'contact',
        onDelete: handleDelete,
        onRestore: handleRestore,
        processing,
    });

    return (
        <>
            <Head title={`${data.first_name} ${data.last_name}`} />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route('contacts.index')}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    {t('Contact', { count: 2 })}
                </Link>
                <span className="mx-2 font-medium text-indigo-600">/</span>
                {data.first_name} {data.last_name}
            </h1>
            {contact.deleted_at && showDeleteControls()}
            <div className="max-w-3xl overflow-hidden rounded-sm bg-white shadow-sm">
                <form onSubmit={handleSubmit}>
                    <div className="-mr-6 -mb-8 flex flex-wrap p-8">
                        <Field
                            label="first_name"
                            value={t('First name')}
                            errors={errors.first_name}
                        >
                            <TextInput
                                name="first_name"
                                value={data.first_name}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData('first_name', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="last_name"
                            value={t('Last name')}
                            errors={errors.last_name}
                        >
                            <TextInput
                                name="last_name"
                                value={data.last_name}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData('last_name', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="organization_id"
                            value={t('Organization', { count: 1 })}
                            errors={errors.organization_id}
                        >
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
                        </Field>

                        <Field
                            label="email"
                            value={t('Email')}
                            errors={errors.email}
                        >
                            <TextInput
                                name="email"
                                type="email"
                                value={data.email}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('email', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="phone"
                            value={t('Phone')}
                            errors={errors.phone}
                        >
                            <TextInput
                                name="phone"
                                value={data.phone}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="address"
                            value={t('Address')}
                            errors={errors.address}
                        >
                            <TextInput
                                name="address"
                                value={data.address}
                                maxLength={150}
                                handleChange={(e) =>
                                    setData('address', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="city"
                            value={t('City')}
                            errors={errors.city}
                        >
                            <TextInput
                                name="city"
                                value={data.city}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('city', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="region"
                            value={t('Province/State')}
                            errors={errors.region}
                        >
                            <TextInput
                                name="region"
                                value={data.region}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData('region', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="country"
                            value={t('Country')}
                            errors={errors.country}
                        >
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
                        </Field>

                        <Field
                            label="postal_code"
                            value={t('Postal Code')}
                            errors={errors.postal_code}
                        >
                            <TextInput
                                name="postal_code"
                                value={data.postal_code}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData('postal_code', e.target.value)
                                }
                            />
                        </Field>
                    </div>
                    <div className="flex items-center border-t border-gray-200 bg-gray-100 px-8 py-4">
                        {!contact.deleted_at && showDeleteControls()}
                        <LoadingButton
                            processing={processing}
                            type="submit"
                            className="btn-indigo ml-auto"
                        >
                            {t('Update Contact')}
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Edit.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Edit;
