import AnchorLink from '@/Components/AnchorLink.js';
import Icon from '@/Components/Icon';
import Layout from '@/Components/Layout';
import Pagination from '@/Components/Pagination';
import SearchFilter from '@/Components/SearchFilter.js';
import { PageProps, PaginatedData, User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface IndexPageProps extends PageProps {
    users: PaginatedData<User>;
}

const Index = () => {
    const { t } = useTranslation();

    const { users } = usePage<IndexPageProps>().props;

    const {
        data,
        meta: { links },
    } = users;

    return (
        <>
            <Head title={t('User', { count: 2 })} />
            <h1 className="mb-8 text-3xl font-bold">
                {t('User', { count: 2 })}
            </h1>
            <div className="mb-6 flex items-center justify-between">
                <SearchFilter />
                <AnchorLink
                    className="btn-indigo focus:outline-hidden"
                    href={route('users.create')}
                    style="btn"
                >
                    <span className="md:hidden">{t('Create')}</span>

                    <span className="hidden md:inline">{t('Create User')}</span>
                </AnchorLink>
            </div>
            <div className="overflow-x-auto rounded-sm bg-white shadow-sm">
                <table className="w-full whitespace-nowrap">
                    <thead>
                        <tr className="text-left font-bold">
                            <th className="px-6 pt-5 pb-4">{t('Name')}</th>
                            <th className="px-6 pt-5 pb-4">{t('Email')}</th>
                            <th className="px-6 pt-5 pb-4" colSpan={2}>
                                {t('Role')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.map(({ id, name, email, owner, deleted_at }) => {
                            return (
                                <tr
                                    key={id}
                                    className="focus-within:bg-gray-100 hover:bg-gray-100"
                                >
                                    <td className="border-t">
                                        <Link
                                            href={route('users.edit', id)}
                                            className="flex items-center px-6 py-4 focus:text-indigo-700 focus:outline-hidden"
                                        >
                                            {name}
                                            {deleted_at && (
                                                <Icon
                                                    name="trash"
                                                    className="ml-2 h-3 w-3 shrink-0 fill-current text-gray-400"
                                                />
                                            )}
                                        </Link>
                                    </td>
                                    <td className="border-t">
                                        <Link
                                            tabIndex={-1}
                                            href={route('users.edit', id)}
                                            className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                        >
                                            {email}
                                        </Link>
                                    </td>
                                    <td className="border-t">
                                        <Link
                                            tabIndex={-1}
                                            href={route('users.edit', id)}
                                            className="focus:text-indigo flex items-center px-6 py-4 focus:outline-hidden"
                                        >
                                            {owner ? t('Owner') : t('User')}
                                        </Link>
                                    </td>
                                    <td className="w-px border-t">
                                        <Link
                                            tabIndex={-1}
                                            href={route('users.edit', id)}
                                            className="flex items-center px-4 focus:outline-hidden"
                                        >
                                            <Icon
                                                name="cheveron-right"
                                                className="block h-6 w-6 fill-current text-gray-400"
                                            />
                                        </Link>
                                    </td>
                                </tr>
                            );
                        })}
                        {data.length === 0 && (
                            <tr>
                                <td className="border-t px-6 py-4" colSpan={4}>
                                    {t('No users found.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={links} />
        </>
    );
};

Index.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Index;
