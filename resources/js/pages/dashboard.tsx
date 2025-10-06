import { usePageActions } from '@/contexts/page-context';
import { dashboard } from '@/routes';
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
                title: 'Dashboard',
                href: dashboard().url,
            },
        ],
        [],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    return (
        <>
            <Head title={t('Dashboard')} />

            <h1 className="mb-8 text-3xl font-bold">{t('Dashboard')}</h1>

            <p
                className="mb-6 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('welcome_message', {
                        inertia_link: '<a href="https://inertiajs.com" class="underline underline-offset-4">Inertia.js</a>',
                        react_link: '<a href="https://react.dev/" class="underline underline-offset-4">React</a>',
                        github_link:
                            '<a href="https://github.com/fouteox/pingcrm-react-inertia-laravel" class="underline underline-offset-4">GitHub</a>',
                    }),
                }}
            />

            <p
                className="leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('original_react_credit', {
                        app_link: '<a href="https://github.com/liorocks/pingcrm-react" class="underline underline-offset-4">Application</a>',
                        author_link: '<a href="https://github.com/liorocks" class="underline underline-offset-4">@liorocks</a>',
                    }),
                }}
            />

            <p
                className="mb-12 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('original_vue_credit', {
                        app_link: '<a href="https://demo.inertiajs.com/" class="underline underline-offset-4">Application</a>',
                        author_link: '<a href="https://github.com/reinink" class="underline underline-offset-4">@reinink</a>',
                    }),
                }}
            />
        </>
    );
}
