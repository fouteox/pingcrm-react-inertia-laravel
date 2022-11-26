import { Link, useForm, Head } from "@inertiajs/inertia-react";
import Layout from "@/Shared/Layout";
import LoadingButton from "@/Shared/LoadingButton";
import TextInput from "@/Shared/TextInput";
import SelectInput from "@/Shared/SelectInput";
import InputLabel from "@/Shared/InputLabel";
import InputError from "@/Shared/InputError";

const Create = () => {
    const { data, setData, errors, post, processing } = useForm({
        name: "",
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
        post(route("organizations.store"));
    }

    return (
        <>
            <Head title="Create Organization" />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route("organizations.index")}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    Organizations
                </Link>
                <span className="font-medium text-indigo-600"> /</span> Create
            </h1>
            <div className="max-w-3xl overflow-hidden bg-white rounded shadow">
                <form onSubmit={handleSubmit}>
                    <div className="flex flex-wrap p-8 -mb-8 -mr-6">
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="name" value="Name:" />
                            <TextInput
                                name="name"
                                value={data.name}
                                handleChange={(e) =>
                                    setData("name", e.target.value)
                                }
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="email" value="Email:" />
                            <TextInput
                                name="email"
                                type="email"
                                value={data.email}
                                handleChange={(e) =>
                                    setData("email", e.target.value)
                                }
                            />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="phone" value="Phone:" />
                            <TextInput
                                name="phone"
                                value={data.phone}
                                handleChange={(e) =>
                                    setData("phone", e.target.value)
                                }
                            />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="adsress" value="Address:" />
                            <TextInput
                                name="address"
                                value={data.address}
                                handleChange={(e) =>
                                    setData("address", e.target.value)
                                }
                            />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel forInput="city" value="City:" />
                            <TextInput
                                name="city"
                                value={data.city}
                                handleChange={(e) =>
                                    setData("city", e.target.value)
                                }
                            />
                        </div>
                        <div className="w-full pb-7 pr-6 lg:w-1/2">
                            <InputLabel
                                forInput="region"
                                value="Province/State:"
                            />
                            <TextInput
                                name="region"
                                value={data.region}
                                handleChange={(e) =>
                                    setData("region", e.target.value)
                                }
                            />
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
                                value="Postal code:"
                            />
                            <TextInput
                                label="Postal Code"
                                name="postal_code"
                                type="text"
                                errors={errors.postal_code}
                                value={data.postal_code}
                                handleChange={(e) =>
                                    setData("postal_code", e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="flex items-center justify-end px-8 py-4 bg-gray-100 border-t border-gray-200">
                        <LoadingButton
                            loading={processing}
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

Create.layout = (page) => (
    <Layout title="Create Organization" children={page} />
);

export default Create;
