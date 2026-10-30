<script lang="ts">
    import { router, usePoll } from '@inertiajs/svelte';
    import AppHead from '@/components/app-head.svelte';
    import { Button } from '@/components/ui/button';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    let { retryAfter, retryUrl, listUrl }: { retryAfter: number; retryUrl: string; listUrl: string } = $props();

    const { t } = useTranslation();
    let retrying = $state(false);

    // svelte-ignore state_referenced_locally
    const { start, stop } = usePoll(
        retryAfter * 1000,
        {
            onStart: () => (retrying = true),
            onFinish: () => (retrying = false),
            onNetworkError: () => false,
        },
        { mode: 'rest' },
    );

    function visit(url: string, replace = false) {
        router.get(
            url,
            {},
            {
                replace,
                onStart: () => {
                    stop();
                    retrying = true;
                },
                onFinish: () => (retrying = false),
                onNetworkError: () => {
                    start();

                    return false;
                },
            },
        );
    }
</script>

<section class="mx-auto flex max-w-xl flex-col gap-6 py-12" aria-labelledby="search-unavailable-title">
    <AppHead title={t('Search is temporarily unavailable.')} />

    <div class="space-y-3">
        <h1 id="search-unavailable-title" class="text-3xl font-bold">
            {t('Search is temporarily unavailable.')}
        </h1>
        <p class="text-muted-foreground">
            {t('We are retrying automatically. You can also try again now or view the list without searching.')}
        </p>
    </div>

    <div class="flex flex-wrap gap-3">
        <Button onclick={() => visit(retryUrl, true)} disabled={retrying} aria-busy={retrying}>
            {t('Try again')}
        </Button>
        <Button onclick={() => visit(listUrl)} variant="outline">
            {t('View the list without searching')}
        </Button>
    </div>
</section>
