import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { DeletionControls } from '@/components/deletion-controls';
import { SubmitButton } from '@/components/submit-button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { FieldGroup } from '@/components/ui/field';
import { UserFormFields, type UserFormData } from '@/components/user-form-fields';
import type { UserResource } from '@/types/resources';
import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/UsersController';
import users from '@/wayfinder/routes/users';

type EditPageProps = {
    user: UserResource;
};

export default function Edit() {
    const { t } = useTranslation();

    const { user } = usePage<EditPageProps>().props;

    const form = useForm<UserFormData>({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        password: '',
        owner: user.owner ? '1' : '0',
    });

    function onSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();

        form.transform((data) => ({
            ...data,
            password: data.password === '' ? undefined : data.password,
        }));

        form.submit(update(user), {
            preserveScroll: true,
            onSuccess: () => form.reset('password'),
        });
    }

    return (
        <>
            <Head title={`${form.data.first_name} ${form.data.last_name}`} />

            {!user.can_delete ? (
                <Alert className="mb-6 max-w-3xl items-center border-yellow-500 bg-yellow-100 text-yellow-800 dark:border-yellow-600/30 dark:bg-yellow-600/10 dark:text-yellow-500">
                    <AlertDescription className="flex w-full items-center justify-between text-yellow-700 dark:text-yellow-500/90">
                        {t('Updating the demo user is not allowed.')}
                    </AlertDescription>
                </Alert>
            ) : (
                user.deleted_at && (
                    <DeletionControls
                        isDeleted={!!user.deleted_at}
                        resourceType="user"
                        canDelete={user.can_delete}
                        deleteAction={destroy(user)}
                        restoreAction={restore(user)}
                    />
                )
            )}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit User')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <UserFormFields form={form} disabled={!user.can_delete} />

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!user.deleted_at && user.can_delete && (
                                <DeletionControls
                                    isDeleted={!!user.deleted_at}
                                    resourceType="user"
                                    canDelete={user.can_delete}
                                    deleteAction={destroy(user)}
                                    restoreAction={restore(user)}
                                />
                            )}

                            <SubmitButton processing={form.processing} recentlySuccessful={form.recentlySuccessful} disabled={!user.can_delete}>
                                {t('Update User')}
                            </SubmitButton>
                        </div>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}

Edit.layout = ({ user }: EditPageProps) => ({
    breadcrumbs: [
        {
            title: 'User',
            count: 2,
            href: users.index().url,
        },
        {
            title: `${user.first_name} ${user.last_name}`,
            href: users.edit(user.id).url,
        },
    ],
});
