import Layout from "@/Shared/Layout";
import { Head } from "@inertiajs/inertia-react";

const Index = () => {
    return (
        <>
            <Head title="Reports" />
            <h1 className="mb-8 text-3xl font-bold">Reports</h1>
        </>
    );
};

Index.layout = (page) => <Layout title="Reports" children={page} />;

export default Index;
