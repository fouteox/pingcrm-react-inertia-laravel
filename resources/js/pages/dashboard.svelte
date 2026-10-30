<script lang="ts" module>
    import { dashboard } from '@/wayfinder/routes';

    export const layout = () => ({ breadcrumbs: [{ title: 'Dashboard', href: dashboard().url }] });
</script>

<script lang="ts">
    import AppHead from '@/components/app-head.svelte';
    import Trans from '@/components/trans.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    const { t } = useTranslation();
</script>

<AppHead title={t('Dashboard')} />

<h1 class="mb-8 text-3xl font-bold">{t('Dashboard')}</h1>

<!--
    Each snippet declared inside <Trans> is passed to it as a prop of the same
    name, and renders the matching <tag>…</tag> of the translation.
-->
<p class="mb-6 leading-normal">
    <Trans i18nKey="welcome_message">
        {#snippet inertia_link(text: string)}
            <a href="https://inertiajs.com" class="underline underline-offset-4">{text}</a>
        {/snippet}
        {#snippet svelte_link(text: string)}
            <a href="https://svelte.dev/" class="underline underline-offset-4">{text}</a>
        {/snippet}
        {#snippet github_link(text: string)}
            <a href="https://github.com/fouteox/pingcrm-react-inertia-laravel" class="underline underline-offset-4">{text}</a>
        {/snippet}
    </Trans>
</p>

<p class="leading-normal">
    <Trans i18nKey="original_react_credit">
        {#snippet app_link()}
            <a href="https://github.com/liorocks/pingcrm-react" class="underline underline-offset-4">Ping CRM React</a>
        {/snippet}
        {#snippet author_link()}
            <a href="https://github.com/liorocks" class="underline underline-offset-4">liorocks</a>
        {/snippet}
    </Trans>
</p>

<p class="mb-12 leading-normal">
    <Trans i18nKey="original_vue_credit">
        {#snippet app_link()}
            <a href="https://demo.inertiajs.com/" class="underline underline-offset-4">Ping CRM</a>
        {/snippet}
        {#snippet author_link()}
            <a href="https://github.com/reinink" class="underline underline-offset-4">Jonathan Reinink</a>
        {/snippet}
    </Trans>
</p>
