import { Head } from '@inertiajs/react';
import { Trans, useTranslation } from 'react-i18next';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { dashboard } from '@/wayfinder/routes';

export default function Dashboard() {
    const { t } = useTranslation();

    useBreadcrumbs([{ title: 'Dashboard', href: dashboard().url }]);

    return (
        <>
            <Head title={t('Dashboard')} />

            <h1 className="mb-8 text-3xl font-bold">{t('Dashboard')}</h1>

            <p className="mb-6 leading-normal">
                <Trans
                    i18nKey="welcome_message"
                    components={{
                        inertia_link: <a href="https://inertiajs.com" className="underline underline-offset-4" />,
                        react_link: <a href="https://react.dev/" className="underline underline-offset-4" />,
                        github_link: <a href="https://github.com/fouteox/pingcrm-react-inertia-laravel" className="underline underline-offset-4" />,
                    }}
                />
            </p>

            <p className="leading-normal">
                <Trans
                    i18nKey="original_react_credit"
                    components={{
                        app_link: <a href="https://github.com/liorocks/pingcrm-react" className="underline underline-offset-4" />,
                        author_link: <a href="https://github.com/liorocks" className="underline underline-offset-4" />,
                    }}
                />
            </p>

            <p className="mb-12 leading-normal">
                <Trans
                    i18nKey="original_vue_credit"
                    components={{
                        app_link: <a href="https://demo.inertiajs.com/" className="underline underline-offset-4" />,
                        author_link: <a href="https://github.com/reinink" className="underline underline-offset-4" />,
                    }}
                />
            </p>
        </>
    );
}
