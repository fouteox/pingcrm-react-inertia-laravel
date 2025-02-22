import Layout from '@/Components/Layout';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';

const Index = () => {
    return (
        <>
            <Head title="Reports" />
            <h1 className="mb-8 text-3xl font-bold">Reports</h1>
        </>
    );
};

Index.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Index;
