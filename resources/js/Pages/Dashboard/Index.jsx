import Layout from "@/Shared/Layout";
import { Head } from "@inertiajs/react";

const Dashboard = () => {
    return (
        <>
            <Head title="Dashboard" />
            <h1 className="mb-8 text-3xl font-bold">Dashboard</h1>
            <p className="mb-6 leading-normal">
                Hey there! Welcome to Ping CRM, a demo app designed to help
                illustrate how
                <a
                    className="mx-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://inertiajs.com"
                >
                    Inertia.js
                </a>
                v1.0 works with
                <a
                    className="ml-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://reactjs.org/"
                >
                    React
                </a>
                . You can find the source code on
                <a
                    className="ml-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://github.com/fouteox/pingcrm-react"
                >
                    GitHub
                </a>
                .
            </p>

            <p className="mb-6 leading-normal">
                This application runs on a raspberry.
            </p>

            <p className="leading-normal">
                Original
                <a
                    className="mx-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://pingcrm-react.herokuapp.com/"
                >
                    application
                </a>
                with Laravel and React by
                <a
                    className="ml-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://github.com/Landish"
                >
                    @landish
                </a>
                .
            </p>
            <p className="mb-12 leading-normal">
                Original
                <a
                    className="mx-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://demo.inertiajs.com/"
                >
                    application
                </a>
                with Laravel and VueJS by
                <a
                    className="ml-1 text-indigo-600 underline hover:text-orange-500"
                    href="https://github.com/reinink"
                >
                    @reinink
                </a>
                .
            </p>
        </>
    );
};

Dashboard.layout = (page) => <Layout children={page} />;

export default Dashboard;
