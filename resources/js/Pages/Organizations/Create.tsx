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
        name: '',
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
        post(route('organizations.store'));
    }

    return (
        <>
            <Head title="Create Organization" />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route('organizations.index')}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    Organizations
                </Link>
                <span className="font-medium text-indigo-600"> /</span> Create
            </h1>
            <div className="max-w-3xl overflow-hidden rounded-sm bg-white shadow-sm">
                <form onSubmit={handleSubmit}>
                    <div className="-mr-6 -mb-8 flex flex-wrap p-8">
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel forInput="name" value="Name:" />
                            <TextInput
                                name="name"
                                value={data.name}
                                maxLength={100}
                                handleChange={(e) =>
                                    setData('name', e.target.value)
                                }
                            />
                            <InputError message={errors.name} />
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
                            <InputLabel forInput="phone" value="Phone:" />
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
                            <InputLabel forInput="adsress" value="Address:" />
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
                            <InputLabel forInput="city" value="City:" />
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
                                value="Province/State:"
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
                            <InputLabel forInput="country" value="Country:" />
                            <SelectInput
                                name="country"
                                value={data.country}
                                onChange={(e) =>
                                    setData('country', e.target.value)
                                }
                            >
                                <option value=""></option>
                                <option value="CA">Canada</option>
                                <option value="US">United States</option>
                            </SelectInput>
                        </div>
                        <div className="w-full pr-6 pb-7 lg:w-1/2">
                            <InputLabel
                                forInput="postal_code"
                                value="Postal code:"
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
                            Create Organization
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Create.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Create;
