<script lang="ts" module>
    import type { AddressFormData } from '@/components/address-fields.svelte';

    export type ContactFormData = AddressFormData & {
        first_name: string;
        last_name: string;
        organization_id: string;
        email: string;
    };
</script>

<script lang="ts">
    import type { InertiaForm } from '@inertiajs/svelte';
    import AddressFields from '@/components/address-fields.svelte';
    import InputField from '@/components/input-field.svelte';
    import SelectField from '@/components/select-field.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import type { UserOrganizationCollection } from '@/types/resources';

    let { form = $bindable(), organizations }: { form: InertiaForm<ContactFormData>; organizations: UserOrganizationCollection } = $props();

    const { t } = useTranslation();
</script>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <InputField
        id="first_name"
        label={t('First name')}
        autocomplete="given-name"
        bind:value={form.first_name}
        error={form.errors.first_name}
        required
        maxlength={25}
        disabled={form.processing}
    />
    <InputField
        id="last_name"
        label={t('Last name')}
        autocomplete="family-name"
        bind:value={form.last_name}
        error={form.errors.last_name}
        required
        maxlength={25}
        disabled={form.processing}
    />
</div>
<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <SelectField
        id="organization_id"
        label={t('Organization', { count: 1 })}
        bind:value={() => form.organization_id || '0', (value) => (form.organization_id = value === '0' ? '' : value)}
        error={form.errors.organization_id}
        disabled={form.processing}
        items={[{ value: '0', label: t('None') }, ...organizations.map(({ id, name }) => ({ value: id.toString(), label: name }))]}
    />
    <InputField
        id="email"
        label={t('Email')}
        type="email"
        autocomplete="email"
        bind:value={form.email}
        error={form.errors.email}
        maxlength={50}
        disabled={form.processing}
    />
</div>
<AddressFields bind:form disabled={form.processing} />
