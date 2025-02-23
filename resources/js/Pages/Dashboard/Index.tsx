import Layout from '@/Components/Layout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

const Dashboard = () => {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Dashboard')} />
            <h1 className="mb-8 text-3xl font-bold">{t('Dashboard')}</h1>
            <p
                className="mb-6 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('welcome_message', {
                        inertia_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://inertiajs.com">Inertia.js</a>',
                        react_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://react.dev/">React</a>',
                        github_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://github.com/fouteox/pingcrm-react-inertia-laravel">GitHub</a>',
                    }),
                }}
            />

            <p
                className="leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('original_react_credit', {
                        app_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://github.com/liorocks/pingcrm-react">Application</a>',
                        author_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://github.com/liorocks">@liorocks</a>',
                    }),
                }}
            />

            <p
                className="mb-12 leading-normal"
                dangerouslySetInnerHTML={{
                    __html: t('original_vue_credit', {
                        app_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://demo.inertiajs.com/">Application</a>',
                        author_link:
                            '<a class="text-indigo-600 underline hover:text-orange-500" href="https://github.com/reinink">@reinink</a>',
                    }),
                }}
            />
        </>
    );
};

Dashboard.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Dashboard;
