<script lang="ts" module>
    /**
     * `<script module>` runs once when the file is imported, not per instance.
     * Exporting `layout` here is how a Svelte page passes props to its layout
     * (the React version used `Login.layout = () => ({ ... })`).
     */
    export const layout = () => ({
        authTitle: 'Log in to your account',
        authDescription: 'Enter your email and password below to log in',
    });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import LoaderCircle from '@lucide/svelte/icons/loader-circle';
    import AppHead from '@/components/app-head.svelte';
    import InputField from '@/components/input-field.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Label } from '@/components/ui/label';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import login from '@/wayfinder/routes/login';

    // Page props come straight from `Inertia::render('auth/login', [...])`.
    let { status }: { status?: string } = $props();

    const { t } = useTranslation();

    // The Svelte form object is reactive: fields are read and bound directly
    // (`form.email`), no `data` / `setData` pair as in React.
    const form = useForm({
        email: 'johndoe@example.com',
        password: 'secret',
        remember: false,
    });

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();

        form.submit(login.store(), {
            onFinish: () => form.reset('password'),
        });
    }
</script>

<AppHead title={t('Login')} />

<form class="flex flex-col gap-6" {onsubmit}>
    <div class="grid gap-6">
        <InputField label={t('Email')} error={form.errors.email} id="email" type="email" required autocomplete="email" bind:value={form.email} />

        <InputField
            label={t('Password')}
            error={form.errors.password}
            id="password"
            type="password"
            required
            autocomplete="current-password"
            bind:value={form.password}
        />

        <div class="flex items-center space-x-3">
            <Checkbox id="remember" name="remember" bind:checked={form.remember} />
            <Label for="remember">{t('Remember me')}</Label>
        </div>

        <Button type="submit" class="mt-4 w-full" disabled={form.processing}>
            {#if form.processing}
                <LoaderCircle class="size-4 animate-spin" />
            {/if}
            {t('Log in')}
        </Button>
    </div>
</form>

{#if status}
    <div class="mb-4 text-center text-sm font-medium text-green-600">{status}</div>
{/if}
