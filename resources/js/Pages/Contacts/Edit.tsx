import DangerButton from '@/Components/DangerButton';
import DeleteButton from '@/Components/DeleteButton';
import { Field } from '@/Components/Form/Field';
import Layout from '@/Components/Layout';
import LoadingButton from '@/Components/LoadingButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import TrashedMessage from '@/Components/TrashedMessage';
import { Contact, FormAction, Organization, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface EditPageProps extends PageProps {
    contact: Contact;
    organizations: Organization[];
}

const Edit = () => {
    const { t } = useTranslation();

    const [showModal, setShowModal] = useState(false);

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

    const deleteContact: FormAction<HTMLButtonElement> = (e) => {
        e.preventDefault();

        destroy(route('contacts.destroy', contact.id), {
            onFinish: () => setShowModal(false),
        });
    };

    const restoreContact: FormAction<HTMLButtonElement> = (e) => {
        e.preventDefault();

        put(route('contacts.restore', contact.id), {
            onFinish: () => setShowModal(false),
        });
    };

    function modalContent() {
        const bodyModal = contact.deleted_at
            ? t('Are you sure you want to restore this contact?')
            : t('Are you sure you want to delete this contact?');
        const actionModal = contact.deleted_at ? restoreContact : deleteContact;
        return (
            <>
                {contact.deleted_at ? (
                    <TrashedMessage onRestore={() => setShowModal(true)}>
                        {t('This contact has been deleted.')}
                    </TrashedMessage>
                ) : (
                    <DeleteButton onClick={() => setShowModal(true)}>
                        {t('Delete Contact')}
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
                                {contact.deleted_at
                                    ? t('Restore Contact')
                                    : t('Delete Contact')}
                            </DangerButton>
                        </div>
                    </div>
                </Modal>
            </>
        );
    }

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
            {contact.deleted_at && modalContent()}
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
                            value={t('Organization')}
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
                        {!contact.deleted_at && modalContent()}
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
