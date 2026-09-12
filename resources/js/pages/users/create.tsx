import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { SubmitButton } from '@/components/submit-button';
import { FieldGroup } from '@/components/ui/field';
import { UserFormFields, type UserFormData } from '@/components/user-form-fields';
import { store } from '@/wayfinder/App/Http/Controllers/UsersController';
import users from '@/wayfinder/routes/users';

export default function Create() {
    const { t } = useTranslation();

    const form = useForm<UserFormData>({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        owner: '0',
    });

    function onSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <>
            <Head title={t('Create User')} />

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Create User')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <UserFormFields form={form} creating />

                        <div className="flex justify-end">
                            <SubmitButton processing={form.processing}>{t('Create User')}</SubmitButton>
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
            title: 'Users',
            count: 2,
            href: users.index().url,
        },
        {
            title: 'Create',
            href: users.create().url,
        },
    ],
});
