import { Link, usePage, useForm, Head } from "@inertiajs/react";
import Layout from "@/Shared/Layout";
import LoadingButton from "@/Shared/LoadingButton";
import TextInput from "@/Shared/TextInput";
import SelectInput from "@/Shared/SelectInput";
import InputLabel from "@/Shared/InputLabel";
import InputError from "@/Shared//InputError";

const Create = () => {
    const { organizations } = usePage().props;
    const { data, setData, errors, post, processing } = useForm({
        first_name: "",
        last_name: "",
        organization_id: "",
        email: "",
        phone: "",
        address: "",
        city: "",
        region: "",
        country: "",
        postal_code: "",
    });

    function handleSubmit(e) {
        e.preventDefault();
        post(route("contacts.store"));
    }

    return (
        <>
            <Head title="Create Contact" />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route("contacts.index")}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    Contacts
                </Link>
                <span className="font-medium text-indigo-600"> /</span> Create
            </h1>
            <div className="max-w-3xl overflow-hidden bg-white rounded shadow">
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-wrap p-8 -mb-8 -mr-6">
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel
                                forInput="first_name"
                                value="First name:"
                            />
                            <TextInput
                                name="first_name"
                                value={data.first_name}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData("first_name", e.target.value)
                                }
                            />
                            <InputError message={errors.first_name} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel
                                forInput="last_name"
                                value="Last name:"
                            />
                            <TextInput
                                name="last_name"
                                value={data.last_name}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData("last_name", e.target.value)
                                }
                            />
                            <InputError message={errors.last_name} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel
                                forInput="organization_id"
                                value="Organization:"
                            />
                            <SelectInput
                                name="organization_id"
                                value={data.organization_id}
                                onChange={(e) =>
                                    setData("organization_id", e.target.value)
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
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="email" value="Email:" />
                            <TextInput
                                name="email"
                                type="email"
                                value={data.email}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("email", e.target.value)
                                }
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="phone" value="Phone:" />
                            <TextInput
                                name="phone"
                                value={data.phone}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("phone", e.target.value)
                                }
                            />
                            <InputError message={errors.phone} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="address" value="Address:" />
                            <TextInput
                                name="address"
                                value={data.address}
                                maxLength={150}
                                handleChange={(e) =>
                                    setData("address", e.target.value)
                                }
                            />
                            <InputError message={errors.address} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="city" value="City:" />
                            <TextInput
                                name="city"
                                value={data.city}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("city", e.target.value)
                                }
                            />
                            <InputError message={errors.city} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel
                                forInput="region"
                                value="Province/State:"
                            />
                            <TextInput
                                name="region"
                                value={data.region}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("region", e.target.value)
                                }
                            />
                            <InputError message={errors.region} />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="country" value="Country:" />
                            <SelectInput
                                name="country"
                                value={data.country}
                                onChange={(e) =>
                                    setData("country", e.target.value)
                                }
                            >
                                <option value=""></option>
                                <option value="CA">Canada</option>
                                <option value="US">United States</option>
                            </SelectInput>
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel
                                forInput="postal_code"
                                value="Postal Code:"
                            />
                            <TextInput
                                name="postal_code"
                                value={data.postal_code}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData("postal_code", e.target.value)
                                }
                            />
                            <InputError message={errors.postal_code} />
                        </div>
                    </div>
                    <div className="flex items-center justify-end px-8 py-4 bg-gray-100 border-t border-gray-200">
                        <LoadingButton
                            loading={processing}
                            type="submit"
                            className="btn-indigo"
                        >
                            Create Contact
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Create.layout = (page) => <Layout title="Create Contact" children={page} />;

export default Create;
