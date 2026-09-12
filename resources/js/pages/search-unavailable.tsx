import { Head, router, usePoll } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';

type SearchUnavailableProps = {
    retryAfter: number;
    retryUrl: string;
    listUrl: string;
};

export default function SearchUnavailable({ retryAfter, retryUrl, listUrl }: SearchUnavailableProps) {
    const { t } = useTranslation();
    const [retrying, setRetrying] = useState(false);
    const { start, stop } = usePoll(
        retryAfter * 1000,
        {
            onStart: () => setRetrying(true),
            onFinish: () => setRetrying(false),
            onNetworkError: () => false,
        },
        { mode: 'rest' },
    );

    const visit = (url: string, replace = false) => {
        router.get(
            url,
            {},
            {
                replace,
                onStart: () => {
                    stop();
                    setRetrying(true);
                },
                onFinish: () => setRetrying(false),
                onNetworkError: () => {
                    start();

                    return false;
                },
            },
        );
    };

    return (
        <section className="mx-auto flex max-w-xl flex-col gap-6 py-12" aria-labelledby="search-unavailable-title">
            <Head title={t('Search is temporarily unavailable.')} />

            <div className="space-y-3">
                <h1 id="search-unavailable-title" className="text-3xl font-bold">
                    {t('Search is temporarily unavailable.')}
                </h1>
                <p className="text-muted-foreground">
                    {t('We are retrying automatically. You can also try again now or view the list without searching.')}
                </p>
            </div>

            <div className="flex flex-wrap gap-3">
                <Button onClick={() => visit(retryUrl, true)} disabled={retrying} aria-busy={retrying}>
                    {t('Try again')}
                </Button>
                <Button onClick={() => visit(listUrl)} variant="outline">
                    {t('View the list without searching')}
                </Button>
            </div>
        </section>
    );
}
