import Layout from '@/Components/Layout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

const Index = () => {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Reports')} />
            <h1 className="mb-8 text-3xl font-bold">{t('Reports')}</h1>
        </>
    );
};

Index.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Index;
