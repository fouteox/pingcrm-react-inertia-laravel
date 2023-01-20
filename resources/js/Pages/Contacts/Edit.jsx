import { Link, usePage, useForm, Head } from "@inertiajs/react";
import Layout from "@/Shared/Layout";
import DeleteButton from "@/Shared/DeleteButton";
import LoadingButton from "@/Shared/LoadingButton";
import SecondaryButton from "@/Components/SecondaryButton";
import DangerButton from "@/Shared/DangerButton";
import TextInput from "@/Shared/TextInput";
import SelectInput from "@/Shared/SelectInput";
import TrashedMessage from "@/Shared/TrashedMessage";
import InputLabel from "@/Shared/InputLabel";
import InputError from "@/Shared/InputError";
import Modal from "@/Shared/Modal";
import useModal from "@/Hooks/useModal";

const Edit = () => {
    const { isShowing, toggle } = useModal();
    const { contact, organizations } = usePage().props;
    const {
        data,
        setData,
        errors,
        put,
        processing,
        delete: destroy,
    } = useForm({
        first_name: contact.first_name || "",
        last_name: contact.last_name || "",
        organization_id: contact.organization_id || "",
        email: contact.email || "",
        phone: contact.phone || "",
        address: contact.address || "",
        city: contact.city || "",
        region: contact.region || "",
        country: contact.country || "",
        postal_code: contact.postal_code || "",
    });

    function handleSubmit(e) {
        e.preventDefault();
        put(route("contacts.update", contact.id));
    }

    const deleteContact = (e) => {
        e.preventDefault();
        toggle();
        destroy(route("contacts.destroy", contact.id));
    };

    const restoreContact = (e) => {
        e.preventDefault();
        toggle();
        put(route("contacts.restore", contact.id));
    };

    function modalContent() {
        const bodyModal = contact.deleted_at
            ? "Are you sure you want to restore this contact?"
            : "Are you sure you want to delete this contact?";
        const actionModal = contact.deleted_at ? restoreContact : deleteContact;
        return (
            <>
                {contact.deleted_at ? (
                    <TrashedMessage onRestore={toggle}>
                        This contact has been deleted.
                    </TrashedMessage>
                ) : (
                    <DeleteButton onClick={toggle}>Delete Contact</DeleteButton>
                )}
                <Modal isShowing={isShowing} hide={toggle}>
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            {bodyModal}
                        </h2>

                        <div className="mt-6 flex justify-end">
                            <SecondaryButton onClick={toggle}>
                                Cancel
                            </SecondaryButton>

                            <DangerButton
                                className="ml-3"
                                processing={processing}
                                type="button"
                                onClick={actionModal}
                            >
                                {contact.deleted_at
                                    ? "Restore Contact"
                                    : "Delete Contact"}
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
                    href={route("contacts.index")}
                    className="text-indigo-600 hover:text-indigo-700"
                >
                    Contacts
                </Link>
                <span className="mx-2 font-medium text-indigo-600">/</span>
                {data.first_name} {data.last_name}
            </h1>
            {contact.deleted_at && modalContent()}
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
                            <InputLabel forInput="address" value="Address:" />
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
                                value="Postal Code:"
                            />
                            <TextInput
                                name="postal_code"
                                value={data.postal_code}
                                handleChange={(e) =>
                                    setData("postal_code", e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="flex items-center px-8 py-4 bg-gray-100 border-t border-gray-200">
                        {!contact.deleted_at && modalContent()}
                        <LoadingButton
                            processing={processing}
                            type="submit"
                            className="ml-auto btn-indigo"
                        >
                            Update Contact
                        </LoadingButton>
                    </div>
                </form>
            </div>
        </>
    );
};

Edit.layout = (page) => <Layout children={page} />;

export default Edit;
