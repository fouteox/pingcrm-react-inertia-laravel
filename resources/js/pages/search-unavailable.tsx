import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';

type SearchUnavailableProps = {
    retryUrl: string;
    listUrl: string;
};

export default function SearchUnavailable({ retryUrl, listUrl }: SearchUnavailableProps) {
    const { t } = useTranslation();

    return (
        <section className="mx-auto flex max-w-xl flex-col gap-6 py-12" aria-labelledby="search-unavailable-title">
            <Head title={t('Search is temporarily unavailable.')} />

            <div className="space-y-3">
                <h1 id="search-unavailable-title" className="text-3xl font-bold">
                    {t('Search is temporarily unavailable.')}
                </h1>
                <p className="text-muted-foreground">
                    {t('Search results cannot be shown right now. Try again in a moment, or view the list without searching.')}
                </p>
            </div>

            <div className="flex flex-wrap gap-3">
                <Button render={<Link href={retryUrl} replace />} nativeButton={false}>
                    {t('Try again')}
                </Button>
                <Button render={<Link href={listUrl} />} nativeButton={false} variant="outline">
                    {t('View the list without searching')}
                </Button>
            </div>
        </section>
    );
}
