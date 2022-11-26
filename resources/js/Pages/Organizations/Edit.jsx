import { useState, useEffect } from "react";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage, useForm, Head } from "@inertiajs/inertia-react";
import Layout from "@/Shared/Layout";
import DeleteButton from "@/Shared/DeleteButton";
import LoadingButton from "@/Shared/LoadingButton";
import SecondaryButton from "@/Components/SecondaryButton";
import DangerButton from "@/Shared/DangerButton";
import TextInput from "@/Shared/TextInput";
import InputLabel from "@/Shared/InputLabel";
import InputError from "@/Shared/InputError";
import SelectInput from "@/Shared/SelectInput";
import TrashedMessage from "@/Shared/TrashedMessage";
import Icon from "@/Shared/Icon";
import Modal from "@/Shared/Modal";

const Edit = () => {
    const [openDialog, setOpenDialog] = useState(null);

    const { organization } = usePage().props;
    const {
        data,
        setData,
        errors,
        put,
        processing,
        delete: destroy,
    } = useForm({
        name: organization.name || "",
        email: organization.email || "",
        phone: organization.phone || "",
        address: organization.address || "",
        city: organization.city || "",
        region: organization.region || "",
        country: organization.country || "",
        postal_code: organization.postal_code || "",
    });

    // useEffect(() => {
    //     const inter = setInterval(() => {
    //         console.log("refresh");
    //         Inertia.reload();
    //     }, 5000);

    //     return () => clearTimeout(inter);
    // }, []);

    function handleSubmit(e) {
        e.preventDefault();
        put(route("organizations.update", organization.id));
    }

    function closeModals() {
        setOpenDialog(null);
    }

    const deleteOrganization = (e) => {
        e.preventDefault();
        closeModals();
        destroy(route("organizations.destroy", organization.id));
    };

    function restore() {
        if (confirm("Are you sure you want to restore this organization?")) {
            Inertia.put(route("organizations.restore", organization.id));
        }
    }

    return (
        <>
            <Head title={data.name} />
            <h1 className="mb-8 text-3xl font-bold">
                <Link
                    href={route("organizations.index")}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    Organizations
                </Link>
                <span className="mx-2 font-medium text-indigo-600">/</span>
                {data.name}
            </h1>
            {organization.deleted_at && (
                <TrashedMessage onRestore={restore}>
                    This organization has been deleted.
                </TrashedMessage>
            )}
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
                    <div className="flex items-center px-8 py-4 bg-gray-100 border-t border-gray-200">
                        {!organization.deleted_at && (
                            <>
                                <DeleteButton
                                    onClick={() =>
                                        setOpenDialog("confirm-delete")
                                    }
                                >
                                    Delete Organization
                                </DeleteButton>

                                <Modal
                                    open={openDialog === "confirm-delete"}
                                    onClose={closeModals}
                                >
                                    <div className="p-6">
                                        <h2 className="text-lg font-medium text-gray-900">
                                            Are you sure you want to delete this
                                            organization?
                                        </h2>

                                        <div className="mt-6 flex justify-end">
                                            <SecondaryButton
                                                onClick={closeModals}
                                            >
                                                Cancel
                                            </SecondaryButton>

                                            <DangerButton
                                                className="ml-3"
                                                processing={processing}
                                                type="button"
                                                onClick={deleteOrganization}
                                            >
                                                Delete Organization
                                            </DangerButton>
                                        </div>
                                    </div>
                                </Modal>
                            </>
                        )}
                        <LoadingButton
                            processing={processing}
                            type="submit"
                            className="ml-auto"
                        >
                            Update Organization
                        </LoadingButton>
                    </div>
                </form>
            </div>
            <h2 className="mt-12 text-2xl font-bold">Contacts</h2>
            <div className="mt-6 overflow-x-auto bg-white rounded shadow">
                <table className="w-full whitespace-nowrap">
                    <thead>
                        <tr className="font-bold text-left">
                            <th className="px-6 pt-5 pb-4">Name</th>
                            <th className="px-6 pt-5 pb-4">City</th>
                            <th className="px-6 pt-5 pb-4" colSpan="2">
                                Phone
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {organization.contacts.map(
                            ({ id, name, phone, city, deleted_at }) => {
                                return (
                                    <tr
                                        key={id}
                                        className="hover:bg-gray-100 focus-within:bg-gray-100"
                                    >
                                        <td className="border-t">
                                            <Link
                                                href={route(
                                                    "contacts.edit",
                                                    id
                                                )}
                                                className="flex items-center px-6 py-4 focus:text-indigo focus:outline-none"
                                            >
                                                {name}
                                                {deleted_at && (
                                                    <Icon
                                                        name="trash"
                                                        className="flex-shrink-0 w-3 h-3 ml-2 text-gray-400 fill-current"
                                                    />
                                                )}
                                            </Link>
                                        </td>
                                        <td className="border-t">
                                            <Link
                                                tabIndex="-1"
                                                href={route(
                                                    "contacts.edit",
                                                    id
                                                )}
                                                className="flex items-center px-6 py-4 focus:text-indigo focus:outline-none"
                                            >
                                                {city}
                                            </Link>
                                        </td>
                                        <td className="border-t">
                                            <Link
                                                tabIndex="-1"
                                                href={route(
                                                    "contacts.edit",
                                                    id
                                                )}
                                                className="flex items-center px-6 py-4 focus:text-indigo focus:outline-none"
                                            >
                                                {phone}
                                            </Link>
                                        </td>
                                        <td className="w-px border-t">
                                            <Link
                                                tabIndex="-1"
                                                href={route(
                                                    "contacts.edit",
                                                    id
                                                )}
                                                className="flex items-center px-4"
                                            >
                                                <Icon
                                                    name="cheveron-right"
                                                    className="block w-6 h-6 text-gray-400 fill-current"
                                                />
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            }
                        )}
                        {organization.contacts.length === 0 && (
                            <tr>
                                <td className="px-6 py-4 border-t" colSpan="4">
                                    No contacts found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </>
    );
};

Edit.layout = (page) => <Layout children={page} />;

export default Edit;
