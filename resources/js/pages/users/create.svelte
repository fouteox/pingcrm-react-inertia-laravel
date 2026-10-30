<script lang="ts" module>
    import users from '@/wayfinder/routes/users';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Users', count: 2, href: users.index().url },
            { title: 'Create', href: users.create().url },
        ],
    });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import SubmitButton from '@/components/submit-button.svelte';
    import { FieldGroup } from '@/components/ui/field';
    import UserFormFields, { type UserFormData } from '@/components/user-form-fields.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { store } from '@/wayfinder/App/Http/Controllers/UsersController';

    const { t } = useTranslation();

    let form = $state(
        useForm<UserFormData>({
            first_name: '',
            last_name: '',
            email: '',
            password: '',
            owner: '0',
        }),
    );

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();
        form.submit(store());
    }
</script>

<AppHead title={t('Create User')} />

<div class="max-w-3xl">
    <h2 class="mb-6 text-xl font-semibold">{t('Create User')}</h2>

    <form {onsubmit}>
        <FieldGroup>
            <UserFormFields bind:form creating />

            <div class="flex justify-end">
                <SubmitButton processing={form.processing}>{t('Create User')}</SubmitButton>
            </div>
        </FieldGroup>
    </form>
</div>
