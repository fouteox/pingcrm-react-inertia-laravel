<script lang="ts" module>
    import organizations from '@/wayfinder/routes/organizations';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Organization', count: 2, href: organizations.index().url },
            { title: 'Create', href: organizations.create().url },
        ],
    });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import OrganizationFormFields, { type OrganizationFormData } from '@/components/organization-form-fields.svelte';
    import SubmitButton from '@/components/submit-button.svelte';
    import { FieldGroup } from '@/components/ui/field';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { store } from '@/wayfinder/App/Http/Controllers/OrganizationsController';

    const { t } = useTranslation();

    let form = $state(
        useForm<OrganizationFormData>({
            name: '',
            email: '',
            phone: '',
            address: '',
            city: '',
            region: '',
            country: '',
            postal_code: '',
        }),
    );

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();
        form.submit(store());
    }
</script>

<AppHead title={t('Create Organization')} />

<div class="max-w-3xl">
    <h2 class="mb-6 text-xl font-semibold">{t('Create Organization')}</h2>

    <form {onsubmit}>
        <FieldGroup>
            <OrganizationFormFields bind:form />

            <div class="flex justify-end">
                <SubmitButton processing={form.processing}>{t('Create Organization')}</SubmitButton>
            </div>
        </FieldGroup>
    </form>
</div>
