import { Head } from '@inertiajs/react';
import { Trans, useTranslation } from 'react-i18next';
import { fadogen } from '@/wayfinder/routes';

export default function Dashboard() {
    const { t, i18n } = useTranslation();

    const docsUrl = i18n.language === 'fr' ? 'https://docs.fadogen.app/fr' : 'https://docs.fadogen.app';

    return (
        <>
            <Head title={t('Fadogen')} />

            <h1 className="mb-8 text-3xl font-bold">{t('Fadogen')}</h1>

            <p className="mb-6 leading-normal">
                <Trans
                    i18nKey="fadogen_presentation"
                    components={{
                        fadogen_link: (
                            <a href={docsUrl} className="underline underline-offset-4">
                                Fadogen
                            </a>
                        ),
                    }}
                />
            </p>
        </>
    );
}

Dashboard.layout = () => ({ breadcrumbs: [{ title: 'Fadogen', href: fadogen().url }] });
