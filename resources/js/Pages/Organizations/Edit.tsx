import DangerButton from '@/Components/DangerButton';
import DeleteButton from '@/Components/DeleteButton';
import { Field } from '@/Components/Form/Field';
import Icon from '@/Components/Icon';
import Layout from '@/Components/Layout';
import LoadingButton from '@/Components/LoadingButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import TrashedMessage from '@/Components/TrashedMessage';
import { FormAction, Organization, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface EditPageProps extends PageProps {
    organization: Organization;
}

const Edit = () => {
    const { t } = useTranslation();

    const [showModal, setShowModal] = useState(false);

    const { organization } = usePage<EditPageProps>().props;
    const {
        data,
        setData,
        errors,
        put,
        processing,
        delete: destroy,
    } = useForm({
        name: organization.name || '',
        email: organization.email || '',
        phone: organization.phone || '',
        address: organization.address || '',
        city: organization.city || '',
        region: organization.region || '',
        country: organization.country || '',
        postal_code: organization.postal_code || '',
    });

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        put(route('organizations.update', organization.id));
    }

    const deleteOrganization: FormAction<HTMLButtonElement> = (e) => {
        e.preventDefault();

        destroy(route('organizations.destroy', organization.id), {
            onFinish: () => setShowModal(false),
        });
    };

    const restoreOrganization: FormAction<HTMLButtonElement> = (e) => {
        e.preventDefault();

        put(route('organizations.restore', organization.id), {
            onFinish: () => setShowModal(false),
        });
    };

    function modalContent() {
        const bodyModal = organization.deleted_at
            ? t('Are you sure you want to restore this organization?')
            : t('Are you sure you want to delete this organization?');
        const actionModal = organization.deleted_at
            ? restoreOrganization
            : deleteOrganization;

        return (
            <>
                {organization.deleted_at ? (
                    <TrashedMessage onRestore={() => setShowModal(true)}>
                        {t('This organization has been deleted.')}
                    </TrashedMessage>
                ) : (
                    <DeleteButton onClick={() => setShowModal(true)}>
                        {t('Delete Organization')}
                    </DeleteButton>
                )}
                <Modal show={showModal} onClose={() => setShowModal(false)}>
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            {bodyModal}
                        </h2>

                        <div className="mt-6 flex justify-end">
                            <SecondaryButton
                                onClick={() => setShowModal(false)}
                            >
                                {t('Cancel')}
                            </SecondaryButton>

                            <DangerButton
                                className="ml-3"
                                processing={processing}
                                type="button"
                                onClick={actionModal}
                            >
                                {organization.deleted_at
                                    ? t('Restore Organization')
                                    : t('Delete Organization')}
                            </DangerButton>
                        </div>
                    </div>
                </Modal>
            </>
        );
    }

    return (
        <>
            <Head title={data.name} />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route('organizations.index')}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    {t('Organization', { count: 2 })}
                </Link>
                <span className="mx-2 font-medium text-indigo-600">/</span>
                {data.name}
            </h1>
            {organization.deleted_at && modalContent()}
            <div className="max-w-3xl overflow-hidden rounded-sm bg-white shadow-sm">
                <form onSubmit={handleSubmit}>
                    <div className="-mr-6 -mb-8 flex flex-wrap p-8">
                        <Field
                            label="name"
                            value={t('Name')}
                            errors={errors.name}
                        >
                            <TextInput
                                name="name"
                                value={data.name}
                                maxLength={100}
                                handleChange={(e) =>
                                    setData('name', e.target.value)
                                }
                            />
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
                            label="adsress"
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
                        {!organization.deleted_at && modalContent()}
                        <LoadingButton
                            processing={processing}
                            type="submit"
                            className="ml-auto"
                        >
                            {t('Update Organization')}
                        </LoadingButton>
                    </div>
                </form>
            </div>
            <h2 className="mt-12 text-2xl font-bold">
                {t('Contact', { count: 2 })}
            </h2>
            <div className="mt-6 overflow-x-auto rounded-sm bg-white shadow-sm">
                <table className="w-full whitespace-nowrap">
                    <thead>
                        <tr className="text-left font-bold">
                            <th className="px-6 pt-5 pb-4">{t('Name')}</th>
                            <th className="px-6 pt-5 pb-4">{t('City')}</th>
                            <th className="px-6 pt-5 pb-4" colSpan={2}>
                                {t('Phone')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {organization.contacts.map(
                            ({ id, name, phone, city, deleted_at }) => {
                                return (
                                    <tr
                                        key={id}
                                        className="focus-within:bg-gray-100 hover:bg-gray-100"
                                    >
                                        <td className="border-t">
                                            <Link
                                                href={route(
                                                    'contacts.edit',
                                                    id,
                                                )}
                                                className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                            >
                                                {name}
                                                {deleted_at && (
                                                    <Icon
                                                        name="trash"
                                                        className="ml-2 h-3 w-3 shrink-0 fill-current text-gray-400"
                                                    />
                                                )}
                                            </Link>
                                        </td>
                                        <td className="border-t">
                                            <Link
                                                tabIndex={-1}
                                                href={route(
                                                    'contacts.edit',
                                                    id,
                                                )}
                                                className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                            >
                                                {city}
                                            </Link>
                                        </td>
                                        <td className="border-t">
                                            <Link
                                                tabIndex={-1}
                                                href={route(
                                                    'contacts.edit',
                                                    id,
                                                )}
                                                className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                            >
                                                {phone}
                                            </Link>
                                        </td>
                                        <td className="w-px border-t">
                                            <Link
                                                tabIndex={-1}
                                                href={route(
                                                    'contacts.edit',
                                                    id,
                                                )}
                                                className="flex items-center px-4"
                                            >
                                                <Icon
                                                    name="cheveron-right"
                                                    className="block h-6 w-6 fill-current text-gray-400"
                                                />
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            },
                        )}
                        {organization.contacts.length === 0 && (
                            <tr>
                                <td className="border-t px-6 py-4" colSpan={4}>
                                    {t('No contacts found.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </>
    );
};

Edit.layout = (page: ReactNode) => <Layout>{page}</Layout>;

export default Edit;
