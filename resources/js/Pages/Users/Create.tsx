import FileInput from '@/Components/FileInput';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Layout from '@/Components/Layout';
import LoadingButton from '@/Components/LoadingButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import React, { ReactNode } from 'react';

const Create = () => {
    const { data, setData, errors, post, processing } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        owner: '0',
        photo: '',
    });

    function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post(route('users.store'));
    }

    return (
        <>
            <Head title="Create User" />
            <div>
                <h1 className="mb-8 text-3xl font-bold">
                    <Link
                        href={route('users.index')}
                        className="text-indigo-600 hover:text-indigo-700"
                    >
                        Users
                    </Link>
                    <span className="font-medium text-indigo-600"> /</span>{' '}
                    Create
                </h1>
            </div>
            <div className="max-w-3xl overflow-hidden rounded-sm bg-white shadow-sm">
                <form name="createForm" onSubmit={handleSubmit}>
                    <div className="-mr-6 -mb-8 flex flex-wrap p-8">
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="first_name"
                                value="First name:"
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
                                value="Last name:"
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
                            <InputLabel forInput="email" value="Email:" />
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
                            <InputLabel forInput="password" value="Password:" />
                            <TextInput
                                name="password"
                                type="password"
                                value={data.password}
                                handleChange={(e) =>
                                    setData('password', e.target.value)
                                }
                            />
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel forInput="owner" value="Owner:" />
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
                        </div>
                        <FileInput
                            className="w-full pr-6 pb-8 lg:w-1/2"
                            label="Photo"
                            name="photo"
                            accept="image/*"
                            error={errors.photo}
                            value={data.photo}
                            onChange={(photo) =>
                                setData('photo', photo as unknown as string)
                            }
                        />
                    </div>
                    <div className="flex items-center justify-end border-t border-gray-200 bg-gray-100 px-8 py-4">
                        <LoadingButton
                            processing={processing}
                            type="submit"
                            className="btn-indigo"
                        >
                            Create User
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Create.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Create;
