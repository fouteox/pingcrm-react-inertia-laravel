import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

export default function Dashboard() {
    const { t } = useTranslation();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Dashboard')} />

            <h1 className="mb-8 text-3xl font-bold">{t('Dashboard')}</h1>

            <p
                className="mb-6 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('welcome_message', {
                        inertia_link:
                            '<a href="https://inertiajs.com">Inertia.js</a>',
                        react_link:
                            '<a href="https://react.dev/">React</a>',
                        github_link:
                            '<a href="https://github.com/fouteox/pingcrm-react-inertia-laravel">GitHub</a>',
                    }),
                }}
            />

            <p
                className="leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('original_react_credit', {
                        app_link:
                            '<a href="https://github.com/liorocks/pingcrm-react">Application</a>',
                        author_link:
                            '<a href="https://github.com/liorocks">@liorocks</a>',
                    }),
                }}
            />

            <p
                className="mb-12 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('original_vue_credit', {
                        app_link:
                            '<a href="https://demo.inertiajs.com/">Application</a>',
                        author_link:
                            '<a href="https://github.com/reinink">@reinink</a>',
                    }),
                }}
            />
        </AppLayout>
    );
};
