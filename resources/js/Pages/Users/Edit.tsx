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
import { FormAction, PageProps, User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { ReactNode, useState } from 'react';

interface EditPageProps extends PageProps {
    user: User & { password: string; photo: File | null };
}

const Edit = () => {
    const [showModal, setShowModal] = useState(false);

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

    const deleteUser: FormAction<HTMLButtonElement> = (e) => {
        e.preventDefault();

        destroy(route('users.destroy', user.id), {
            onFinish: () => setShowModal(false),
        });
    };

    const restoreUser: FormAction<HTMLButtonElement> = (e) => {
        e.preventDefault();

        put(route('users.restore', user.id), {
            onFinish: () => setShowModal(false),
        });
    };

    function modalContent() {
        const bodyModal = user.deleted_at
            ? 'Are you sure you want to restore this user?'
            : 'Are you sure you want to delete this user?';
        const actionModal = user.deleted_at ? restoreUser : deleteUser;
        return (
            <>
                {user.deleted_at ? (
                    <TrashedMessage onRestore={() => setShowModal(true)}>
                        This user has been deleted.
                    </TrashedMessage>
                ) : (
                    <DeleteButton onClick={() => setShowModal(true)}>
                        Delete User
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
                                Cancel
                            </SecondaryButton>

                            <DangerButton
                                className="ml-3"
                                processing={processing}
                                type="button"
                                onClick={actionModal}
                            >
                                {user.deleted_at
                                    ? 'Restore User'
                                    : 'Delete User'}
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
            <div className="mb-8 flex max-w-lg justify-start">
                <h1 className="text-3xl font-bold">
                    <Link
                        href={route('users.index')}
                        className="text-indigo-600 hover:text-indigo-700"
                    >
                        Users
                    </Link>
                    <span className="mx-2 font-medium text-indigo-600">/</span>
                    {data.first_name} {data.last_name}
                </h1>
            </div>
            {!user.can_delete ? (
                <div className="mb-6 max-w-3xl rounded-sm border border-yellow-500 bg-yellow-400 p-4">
                    <div className="text-yellow-800">
                        Updating the demo user is not allowed.
                    </div>
                </div>
            ) : (
                user.deleted_at && modalContent()
            )}
            <div className="max-w-3xl overflow-hidden rounded-sm bg-white shadow-sm">
                <form onSubmit={handleSubmit}>
                    <div className="-mr-6 -mb-8 flex flex-wrap p-8">
                        <Field
                            label="first_name"
                            value="First name:"
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
                            value="Last name:"
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
                            value="Email:"
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
                            value="Password:"
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
                            value="Owner:"
                            errors={errors.owner}
                        >
                            <SelectInput
                                name="owner"
                                value={data.owner}
                                onChange={(e) =>
                                    setData('owner', e.target.value)
                                }
                            >
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </SelectInput>
                        </Field>
                    </div>
                    <div className="flex items-center border-t border-gray-200 bg-gray-100 px-8 py-4">
                        {!user.deleted_at && user.can_delete && modalContent()}
                        <LoadingButton
                            processing={processing}
                            disabled={!user.can_delete}
                            type="submit"
                            className="btn-indigo ml-auto"
                        >
                            Update User
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Edit.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Edit;
