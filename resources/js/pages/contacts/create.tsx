import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { ContactFormFields, type ContactFormData } from '@/components/contact-form-fields';
import { SubmitButton } from '@/components/submit-button';
import { FieldGroup } from '@/components/ui/field';
import { store } from '@/wayfinder/App/Http/Controllers/ContactsController';
import contacts from '@/wayfinder/routes/contacts';

type CreatePageProps = {
    organizations: Array<{ id: number; name: string }>;
};

export default function Create() {
    const { t } = useTranslation();

    const { organizations } = usePage<CreatePageProps>().props;
    const form = useForm<ContactFormData>({
        first_name: '',
        last_name: '',
        organization_id: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        region: '',
        country: '',
        postal_code: '',
    });

    function onSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <>
            <Head title={t('Create Contact')} />

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Create Contact')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <ContactFormFields form={form} organizations={organizations} />

                        <div className="flex justify-end">
                            <SubmitButton processing={form.processing}>{t('Create Contact')}</SubmitButton>
                        </div>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}

Create.layout = () => ({
    breadcrumbs: [
        {
            title: 'Contact',
            count: 2,
            href: contacts.index().url,
        },
        {
            title: 'Create',
            href: contacts.create().url,
        },
    ],
});
