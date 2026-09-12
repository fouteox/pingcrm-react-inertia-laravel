import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { OrganizationFormFields, type OrganizationFormData } from '@/components/organization-form-fields';
import { SubmitButton } from '@/components/submit-button';
import { FieldGroup } from '@/components/ui/field';
import { store } from '@/wayfinder/App/Http/Controllers/OrganizationsController';
import organizations from '@/wayfinder/routes/organizations';

export default function Create() {
    const { t } = useTranslation();

    const form = useForm<OrganizationFormData>({
        name: '',
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
            <Head title={t('Create Organization')} />

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Create Organization')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <OrganizationFormFields form={form} />

                        <div className="flex justify-end">
                            <SubmitButton processing={form.processing}>{t('Create Organization')}</SubmitButton>
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
            title: 'Organization',
            count: 2,
            href: organizations.index().url,
        },
        {
            title: 'Create',
            href: organizations.create().url,
        },
    ],
});
