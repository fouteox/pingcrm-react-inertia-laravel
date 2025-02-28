import { usePageActions } from '@/contexts/page-context';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Fadogen',
                href: route('fadogen'),
            },
        ],
        [],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    return (
        <>
            <Head title={t('Fadogen')} />

            <h1 className="mb-8 text-3xl font-bold">{t('Fadogen')}</h1>

            <p
                className="mb-6 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('fadogen_presentation', {
                        fadogen_link: '<a href="https://fadogen.app" class="underline underline-offset-4">Fadogen</a>',
                    }),
                }}
            />
        </>
    );
}
