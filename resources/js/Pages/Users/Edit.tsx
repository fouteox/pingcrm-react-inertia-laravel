import { Field } from '@/Components/Form/Field';
import Layout from '@/Components/Layout';
import LoadingButton from '@/Components/LoadingButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { useDeletionControls } from '@/hooks/useDeletionControls';
import { PageProps, User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface EditPageProps extends PageProps {
    user: User & { password: string; photo: File | null };
}

const Edit = () => {
    const { t } = useTranslation();

    const { user } = usePage<EditPageProps>().props;

    const {
        data,
        setData,
        errors,
        post,
        processing,
        delete: destroy,
        put,
        transform,
    } = useForm({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        password: user.password || '',
        owner: user.owner ? '1' : '0',
        _method: 'PUT',
    });

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();

        transform((data) => ({
            ...data,
            password: data.password === '' ? undefined : data.password,
        }));

        post(route('users.update', user.id));
    }

    const handleDelete = async (id: number) => {
        destroy(route('users.destroy', id));
    };

    const handleRestore = async (id: number) => {
        put(route('users.restore', id));
    };

    const { showDeleteControls } = useDeletionControls({
        resourceId: user.id,
        isDeleted: !!user.deleted_at,
        resourceType: 'user',
        canDelete: user.can_delete,
        onDelete: handleDelete,
        onRestore: handleRestore,
        processing,
    });

    return (
        <>
            <Head title={`${data.first_name} ${data.last_name}`} />
            <div className="mb-8 flex max-w-lg justify-start">
                <h1 className="text-3xl font-bold">
                    <Link
                        href={route('users.index')}
                        className="text-indigo-600 hover:text-indigo-700"
                    >
                        {t('User', { count: 2 })}
                    </Link>
                    <span className="mx-2 font-medium text-indigo-600">/</span>
                    {data.first_name} {data.last_name}
                </h1>
            </div>
            {!user.can_delete ? (
                <div className="mb-6 max-w-3xl rounded-sm border border-yellow-500 bg-yellow-400 p-4">
                    <div className="text-yellow-800">
                        {t('Updating the demo user is not allowed.')}
                    </div>
                </div>
            ) : (
                user.deleted_at && showDeleteControls()
            )}
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
                            label="password"
                            value={t('Password')}
                            errors={errors.password}
                        >
                            <TextInput
                                name="password"
                                type="password"
                                value={data.password}
                                handleChange={(e) =>
                                    setData('password', e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="owner"
                            value={t('Owner')}
                            errors={errors.owner}
                        >
                            <SelectInput
                                name="owner"
                                value={data.owner}
                                onChange={(e) =>
                                    setData('owner', e.target.value)
                                }
                            >
                                <option value="1">{t('Yes')}</option>
                                <option value="0">{t('No')}</option>
                            </SelectInput>
                        </Field>
                    </div>
                    <div className="flex items-center border-t border-gray-200 bg-gray-100 px-8 py-4">
                        {!user.deleted_at &&
                            user.can_delete &&
                            showDeleteControls()}
                        <LoadingButton
                            processing={processing}
                            disabled={!user.can_delete}
                            type="submit"
                            className="btn-indigo ml-auto"
                        >
                            {t('Update User')}
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Edit.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Edit;
