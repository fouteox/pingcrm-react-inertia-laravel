import { usePageActions } from '@/contexts/page-context';
import { fadogen } from '@/routes';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t, i18n } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const docsUrl = i18n.language === 'fr' ? 'https://docs.fadogen.app/fr' : 'https://docs.fadogen.app';

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Fadogen',
                href: fadogen().url,
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
                        fadogen_link: `<a href="${docsUrl}" class="underline underline-offset-4">Fadogen</a>`,
                    }),
                }}
            />
        </>
    );
}
