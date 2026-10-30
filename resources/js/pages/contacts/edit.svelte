<script lang="ts" module>
    import contacts from '@/wayfinder/routes/contacts';
    import type { ContactResource, UserOrganizationCollection } from '@/types/resources';

    type EditPageProps = {
        contact: ContactResource;
        organizations: UserOrganizationCollection;
    };

    // The layout callback receives the page props, so breadcrumbs can use the contact name.
    export const layout = ({ contact }: EditPageProps) => ({
        breadcrumbs: [
            { title: 'Contact', count: 2, href: contacts.index().url },
            { title: `${contact.first_name} ${contact.last_name}`, href: contacts.edit(contact.id).url },
        ],
    });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import ContactFormFields, { type ContactFormData } from '@/components/contact-form-fields.svelte';
    import DeletionControls from '@/components/deletion-controls.svelte';
    import SubmitButton from '@/components/submit-button.svelte';
    import { FieldGroup } from '@/components/ui/field';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/ContactsController';

    let { contact, organizations }: EditPageProps = $props();

    const { t } = useTranslation();

    // The form copies the initial values once: later prop updates must not reset what the user typed.
    // svelte-ignore state_referenced_locally
    let form = $state(
        useForm<ContactFormData>({
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
        }),
    );

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();
        form.submit(update(contact), {
            preserveScroll: true,
        });
    }
</script>

<AppHead title="{form.first_name} {form.last_name}" />

{#if contact.deleted_at}
    <DeletionControls isDeleted={!!contact.deleted_at} resourceType="contact" deleteAction={destroy(contact)} restoreAction={restore(contact)} />
{/if}

<div class="max-w-3xl">
    <h2 class="mb-6 text-xl font-semibold">{t('Edit Contact')}</h2>

    <form {onsubmit}>
        <FieldGroup>
            <ContactFormFields bind:form {organizations} />

            <div class="flex flex-col justify-end gap-4 sm:flex-row">
                {#if !contact.deleted_at}
                    <DeletionControls isDeleted={!!contact.deleted_at} resourceType="contact" deleteAction={destroy(contact)} restoreAction={restore(contact)} />
                {/if}

                <SubmitButton processing={form.processing} recentlySuccessful={form.recentlySuccessful}>
                    {t('Update Contact')}
                </SubmitButton>
            </div>
        </FieldGroup>
    </form>
</div>
