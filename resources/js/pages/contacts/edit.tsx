import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { ContactFormFields, type ContactFormData } from '@/components/contact-form-fields';
import { DeletionControls } from '@/components/deletion-controls';
import { SubmitButton } from '@/components/submit-button';
import { FieldGroup } from '@/components/ui/field';
import type { ContactResource, UserOrganizationCollection } from '@/types/resources';
import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/ContactsController';
import contacts from '@/wayfinder/routes/contacts';

type EditPageProps = {
    contact: ContactResource;
    organizations: UserOrganizationCollection;
};

export default function Edit() {
    const { t } = useTranslation();

    const { contact, organizations } = usePage<EditPageProps>().props;

    const form = useForm<ContactFormData>({
        first_name: contact.first_name || '',
        last_name: contact.last_name || '',
        organization_id: contact.organization_id ? contact.organization_id.toString() : '',
        email: contact.email || '',
        phone: contact.phone || '',
        address: contact.address || '',
        city: contact.city || '',
        region: contact.region || '',
        country: contact.country || '',
        postal_code: contact.postal_code || '',
    });

    function onSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(update(contact), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title={`${form.data.first_name} ${form.data.last_name}`} />

            {contact.deleted_at && (
                <DeletionControls
                    isDeleted={!!contact.deleted_at}
                    resourceType="contact"
                    deleteAction={destroy(contact)}
                    restoreAction={restore(contact)}
                />
            )}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit Contact')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <ContactFormFields form={form} organizations={organizations} />

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!contact.deleted_at && (
                                <DeletionControls
                                    isDeleted={!!contact.deleted_at}
                                    resourceType="contact"
                                    deleteAction={destroy(contact)}
                                    restoreAction={restore(contact)}
                                />
                            )}

                            <SubmitButton processing={form.processing} recentlySuccessful={form.recentlySuccessful}>
                                {t('Update Contact')}
                            </SubmitButton>
                        </div>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}

Edit.layout = ({ contact }: EditPageProps) => ({
    breadcrumbs: [
        {
            title: 'Contact',
            count: 2,
            href: contacts.index().url,
        },
        {
            title: `${contact.first_name} ${contact.last_name}`,
            href: contacts.edit(contact.id).url,
        },
    ],
});
