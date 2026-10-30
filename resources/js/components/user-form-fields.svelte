<script lang="ts" module>
    export type UserFormData = {
        first_name: string;
        last_name: string;
        email: string;
        password: string;
        owner: string;
    };
</script>

<script lang="ts">
    import type { InertiaForm } from '@inertiajs/svelte';
    import InputField from '@/components/input-field.svelte';
    import SelectField from '@/components/select-field.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    let {
        form = $bindable(),
        creating = false,
        disabled = false,
    }: {
        form: InertiaForm<UserFormData>;
        creating?: boolean;
        disabled?: boolean;
    } = $props();

    const { t } = useTranslation();
    const isDisabled = $derived(disabled || form.processing);
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
        disabled={isDisabled}
    />
    <InputField
        id="last_name"
        label={t('Last name')}
        autocomplete="family-name"
        bind:value={form.last_name}
        error={form.errors.last_name}
        required
        maxlength={25}
        disabled={isDisabled}
    />
</div>
<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <InputField
        id="email"
        label={t('Email')}
        type="email"
        autocomplete="email"
        bind:value={form.email}
        error={form.errors.email}
        required
        maxlength={50}
        disabled={isDisabled}
    />
    <InputField
        id="password"
        label={t('Password')}
        type="password"
        autocomplete="new-password"
        bind:value={form.password}
        error={form.errors.password}
        required={creating}
        disabled={isDisabled}
    />
</div>
<SelectField
    id="owner"
    label={t('Owner')}
    bind:value={form.owner}
    error={form.errors.owner}
    disabled={isDisabled}
    items={[
        { value: '1', label: t('Yes') },
        { value: '0', label: t('No') },
    ]}
/>
