<script lang="ts" module>
    import type { AddressFormData } from '@/components/address-fields.svelte';

    export type OrganizationFormData = AddressFormData & {
        name: string;
        email: string;
    };
</script>

<script lang="ts">
    import type { InertiaForm } from '@inertiajs/svelte';
    import AddressFields from '@/components/address-fields.svelte';
    import InputField from '@/components/input-field.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    let { form = $bindable() }: { form: InertiaForm<OrganizationFormData> } = $props();

    const { t } = useTranslation();
</script>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <InputField
        id="name"
        label={t('Name')}
        autocomplete="organization"
        bind:value={form.name}
        error={form.errors.name}
        required
        maxlength={100}
        disabled={form.processing}
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
