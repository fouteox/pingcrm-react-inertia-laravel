import AnchorLink from '@/components/anchor-link';
import InertiaPagination from '@/components/inertia-pagination';
import SearchFilter from '@/components/search-filter';
import { TableContainer } from '@/components/table-container';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, PageProps, PaginatedData, User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight, Trash } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface IndexPageProps extends PageProps {
    users: PaginatedData<User>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User',
        count: 2,
        href: route('users.index'),
    },
];

export default function Index() {
    const { t } = useTranslation();

    const { users } = usePage<IndexPageProps>().props;
    const {
        data,
        meta: { links },
    } = users;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('User', { count: 2 })} />

            <div className="flex h-full w-full flex-col">
                <div className="mb-6 flex items-center justify-between gap-2">
                    <SearchFilter />

                    <div className="flex-shrink-0">
                        <AnchorLink href={route('users.create')}>
                            <span className="md:hidden">{t('Create')}</span>
                            <span className="hidden md:inline">{t('Create User')}</span>
                        </AnchorLink>
                    </div>
                </div>

                <TableContainer>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Email')}</TableHead>
                                <TableHead>{t('Role')}</TableHead>
                                <TableHead className="w-[50px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map(({ id, name, email, owner, deleted_at }) => (
                                <TableRow key={id}>
                                    <TableCell className="relative p-2">
                                        <div className="absolute inset-0 z-10">
                                            <Link href={route('users.edit', id)} prefetch className="block h-full w-full">
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
                                            {name}
                                            {deleted_at && <Trash className="text-muted-foreground ml-2 h-3 w-3 shrink-0" />}
                                        </div>
                                    </TableCell>
                                    <TableCell className="relative p-2">
                                        <div className="absolute inset-0 z-10">
                                            <Link href={route('users.edit', id)} prefetch tabIndex={-1} className="block h-full w-full">
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{email}</div>
                                    </TableCell>
                                    <TableCell className="relative p-2">
                                        <div className="absolute inset-0 z-10">
                                            <Link href={route('users.edit', id)} prefetch tabIndex={-1} className="block h-full w-full">
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
                                            {owner ? t('Owner') : t('User')}
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-px">
                                        <Button asChild variant="ghost" size="icon">
                                            <Link tabIndex={-1} href={route('users.edit', id)} prefetch>
                                                <ChevronRight className="text-muted-foreground h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center">
                                        {t('No users found.')}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </TableContainer>

                <div className="my-6">
                    <InertiaPagination links={links} />
                </div>
            </div>
        </AppLayout>
    );
}
