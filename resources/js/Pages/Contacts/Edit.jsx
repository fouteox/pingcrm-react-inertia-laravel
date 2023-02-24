import { useState } from "react";
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
import InputError from "@/Shared//InputError";
import Modal from "@/Shared/Modal";
import { Field } from "@/Components/Form/Field";

const Edit = () => {
    const [showModal, setShowModal] = useState(false);

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

        destroy(route("contacts.destroy", contact.id), {
            onFinish: () => setShowModal(false),
        });
    };

    const restoreContact = (e) => {
        e.preventDefault();

        put(route("contacts.restore", contact.id), {
            onFinish: () => setShowModal(false),
        });
    };

    function modalContent() {
        const bodyModal = contact.deleted_at
            ? "Are you sure you want to restore this contact?"
            : "Are you sure you want to delete this contact?";
        const actionModal = contact.deleted_at ? restoreContact : deleteContact;
        return (
            <>
                {contact.deleted_at ? (
                    <TrashedMessage onRestore={() => setShowModal(true)}>
                        This contact has been deleted.
                    </TrashedMessage>
                ) : (
                    <DeleteButton onClick={() => setShowModal(true)}>
                        Delete Contact
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
                                    setData("first_name", e.target.value)
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
                                    setData("last_name", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="organization_id"
                            value="Organization:"
                            errors={errors.organization_id}
                        >
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
                                    setData("email", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="phone"
                            value="Phone:"
                            errors={errors.phone}
                        >
                            <TextInput
                                name="phone"
                                value={data.phone}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("phone", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="address"
                            value="Address:"
                            errors={errors.address}
                        >
                            <TextInput
                                name="address"
                                value={data.address}
                                maxLength={150}
                                handleChange={(e) =>
                                    setData("address", e.target.value)
                                }
                            />
                        </Field>

                        <Field label="city" value="City:" errors={errors.city}>
                            <TextInput
                                name="city"
                                value={data.city}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("city", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="region"
                            value="Province/State:"
                            errors={errors.region}
                        >
                            <TextInput
                                name="region"
                                value={data.region}
                                maxLength={50}
                                handleChange={(e) =>
                                    setData("region", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="country"
                            value="Country:"
                            errors={errors.country}
                        >
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
                        </Field>

                        <Field
                            label="postal_code"
                            value="Postal Code:"
                            errors={errors.postal_code}
                        >
                            <TextInput
                                name="postal_code"
                                value={data.postal_code}
                                maxLength={25}
                                handleChange={(e) =>
                                    setData("postal_code", e.target.value)
                                }
                            />
                        </Field>
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
