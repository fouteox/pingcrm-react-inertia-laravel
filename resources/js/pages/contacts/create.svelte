<script lang="ts" module>
    import contacts from '@/wayfinder/routes/contacts';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Contact', count: 2, href: contacts.index().url },
            { title: 'Create', href: contacts.create().url },
        ],
    });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import ContactFormFields, { type ContactFormData } from '@/components/contact-form-fields.svelte';
    import SubmitButton from '@/components/submit-button.svelte';
    import { FieldGroup } from '@/components/ui/field';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { store } from '@/wayfinder/App/Http/Controllers/ContactsController';

    let { organizations }: { organizations: Array<{ id: number; name: string }> } = $props();

    const { t } = useTranslation();

    // `$state` + `let` because the form is passed down with `bind:form` (useForm already returns a reactive proxy).
    let form = $state(
        useForm<ContactFormData>({
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
        }),
    );

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();
        form.submit(store());
    }
</script>

<AppHead title={t('Create Contact')} />

<div class="max-w-3xl">
    <h2 class="mb-6 text-xl font-semibold">{t('Create Contact')}</h2>

    <form {onsubmit}>
        <FieldGroup>
            <ContactFormFields bind:form {organizations} />

            <div class="flex justify-end">
                <SubmitButton processing={form.processing}>{t('Create Contact')}</SubmitButton>
            </div>
        </FieldGroup>
    </form>
</div>
