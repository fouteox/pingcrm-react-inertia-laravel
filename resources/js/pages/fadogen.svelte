<script lang="ts" module>
    import { fadogen } from '@/wayfinder/routes';

    export const layout = () => ({ breadcrumbs: [{ title: 'Fadogen', href: fadogen().url }] });
</script>

<script lang="ts">
    import AppHead from '@/components/app-head.svelte';
    import Trans from '@/components/trans.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    const translator = useTranslation();
    const { t } = translator;

    const docsUrl = $derived(translator.language === 'fr' ? 'https://docs.fadogen.app/fr' : 'https://docs.fadogen.app');
</script>

<AppHead title={t('Fadogen')} />

<h1 class="mb-8 text-3xl font-bold">{t('Fadogen')}</h1>

<p class="mb-6 leading-normal">
    <Trans i18nKey="fadogen_presentation">
        {#snippet fadogen_link()}
            <a href={docsUrl} class="underline underline-offset-4">Fadogen</a>
        {/snippet}
    </Trans>
</p>
