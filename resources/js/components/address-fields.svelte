<script lang="ts" module>
    export type AddressFormData = {
        phone: string;
        address: string;
        city: string;
        region: string;
        country: string;
        postal_code: string;
    };
</script>

<script lang="ts">
    import InputField from '@/components/input-field.svelte';
    import SelectField from '@/components/select-field.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    /**
     * `form` is the Inertia form object (a deeply reactive `$state` proxy).
     * It is declared `$bindable` and the parent passes `bind:form`: Svelte then
     * allows this component to mutate it (`bind:value={form.city}`) and the
     * change is visible everywhere the form is used.
     */
    let {
        form = $bindable(),
        disabled,
    }: {
        form: AddressFormData & { errors: Partial<Record<keyof AddressFormData, string>> };
        disabled: boolean;
    } = $props();

    const { t } = useTranslation();
</script>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <InputField
        id="phone"
        label={t('Phone')}
        type="tel"
        autocomplete="tel"
        bind:value={form.phone}
        error={form.errors.phone}
        maxlength={50}
        {disabled}
    />
    <InputField
        id="address"
        label={t('Address')}
        autocomplete="street-address"
        bind:value={form.address}
        error={form.errors.address}
        maxlength={150}
        {disabled}
    />
</div>
<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <InputField id="city" label={t('City')} autocomplete="address-level2" bind:value={form.city} error={form.errors.city} maxlength={50} {disabled} />
    <InputField
        id="region"
        label={t('Province/State')}
        autocomplete="address-level1"
        bind:value={form.region}
        error={form.errors.region}
        maxlength={50}
        {disabled}
    />
</div>
<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <!--
        Function binding (getter, setter): the select uses '0' for "None"
        while the form stores an empty string.
    -->
    <SelectField
        id="country"
        label={t('Country')}
        bind:value={() => form.country || '0', (value) => (form.country = value === '0' ? '' : value)}
        error={form.errors.country}
        {disabled}
        items={[
            { value: '0', label: t('None') },
            { value: 'CA', label: t('Canada') },
            { value: 'US', label: t('United States') },
        ]}
    />
    <InputField
        id="postal_code"
        label={t('Postal Code')}
        autocomplete="postal-code"
        bind:value={form.postal_code}
        error={form.errors.postal_code}
        maxlength={25}
        {disabled}
    />
</div>
