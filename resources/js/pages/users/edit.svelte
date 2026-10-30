<script lang="ts" module>
    import users from '@/wayfinder/routes/users';
    import type { UserResource } from '@/types/resources';

    type EditPageProps = { user: UserResource };

    export const layout = ({ user }: EditPageProps) => ({
        breadcrumbs: [
            { title: 'User', count: 2, href: users.index().url },
            { title: `${user.first_name} ${user.last_name}`, href: users.edit(user.id).url },
        ],
    });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import DeletionControls from '@/components/deletion-controls.svelte';
    import SubmitButton from '@/components/submit-button.svelte';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { FieldGroup } from '@/components/ui/field';
    import UserFormFields, { type UserFormData } from '@/components/user-form-fields.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/UsersController';

    let { user }: EditPageProps = $props();

    const { t } = useTranslation();

    // svelte-ignore state_referenced_locally
    let form = $state(
        useForm<UserFormData>({
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            email: user.email || '',
            password: '',
            owner: user.owner ? '1' : '0',
        }),
    );

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();

        // An empty password means "keep the current one": don't send it.
        form.transform((data) => ({
            ...data,
            password: data.password === '' ? undefined : data.password,
        }));

        form.submit(update(user), {
            preserveScroll: true,
            onSuccess: () => form.reset('password'),
        });
    }
</script>

<AppHead title="{form.first_name} {form.last_name}" />

{#if !user.can_delete}
    <Alert
        class="mb-6 max-w-3xl items-center border-yellow-500 bg-yellow-100 text-yellow-800 dark:border-yellow-600/30 dark:bg-yellow-600/10 dark:text-yellow-500"
    >
        <AlertDescription class="flex w-full items-center justify-between text-yellow-700 dark:text-yellow-500/90">
            {t('Updating the demo user is not allowed.')}
        </AlertDescription>
    </Alert>
{:else if user.deleted_at}
    <DeletionControls
        isDeleted={!!user.deleted_at}
        resourceType="user"
        canDelete={user.can_delete}
        deleteAction={destroy(user)}
        restoreAction={restore(user)}
    />
{/if}

<div class="max-w-3xl">
    <h2 class="mb-6 text-xl font-semibold">{t('Edit User')}</h2>

    <form {onsubmit}>
        <FieldGroup>
            <UserFormFields bind:form disabled={!user.can_delete} />

            <div class="flex flex-col justify-end gap-4 sm:flex-row">
                {#if !user.deleted_at && user.can_delete}
                    <DeletionControls
                        isDeleted={!!user.deleted_at}
                        resourceType="user"
                        canDelete={user.can_delete}
                        deleteAction={destroy(user)}
                        restoreAction={restore(user)}
                    />
                {/if}

                <SubmitButton processing={form.processing} recentlySuccessful={form.recentlySuccessful} disabled={!user.can_delete}>
                    {t('Update User')}
                </SubmitButton>
            </div>
        </FieldGroup>
    </form>
</div>
