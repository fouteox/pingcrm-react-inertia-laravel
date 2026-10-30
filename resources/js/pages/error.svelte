<script lang="ts">
    import AppHead from '@/components/app-head.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    let { status }: { status: number } = $props();

    const { t } = useTranslation();

    const title = $derived(
        {
            503: t('503: Service Unavailable'),
            500: t('500: Server Error'),
            429: t('429: Too Many Requests'),
            404: t('404: Page Not Found'),
            403: t('403: Forbidden'),
        }[status],
    );

    const description = $derived(
        {
            503: t('Sorry, we are doing some maintenance. Please check back soon.'),
            500: t('Whoops, something went wrong on our servers.'),
            429: t('Sorry, you are making too many requests to our servers.'),
            404: t('Sorry, the page you are looking for could not be found.'),
            403: t('Sorry, you are forbidden from accessing this page.'),
        }[status],
    );
</script>

<div class="flex min-h-screen items-center justify-center bg-indigo-800 p-5 text-indigo-100">
    <AppHead {title} />
    <div class="w-full max-w-md">
        <h1 class="text-3xl">{title}</h1>
        <p class="mt-3 text-lg leading-tight">{description}</p>
    </div>
</div>
