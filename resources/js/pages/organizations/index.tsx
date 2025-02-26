import AnchorLink from '@/components/anchor-link';
import InertiaPagination from '@/components/inertia-pagination';
import SearchFilter from '@/components/search-filter';
import { TableContainer } from '@/components/table-container';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, Organization, PageProps, PaginatedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight, Trash } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface IndexPageProps extends PageProps {
    organizations: PaginatedData<Organization>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Organization',
        count: 2,
        href: route('organizations.index'),
    },
];

export default function Index() {
    const { t } = useTranslation();

    const { organizations } = usePage<IndexPageProps>().props;
    const {
        data,
        meta: { links },
    } = organizations;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Organization', { count: 2 })} />

            <div className="flex h-full w-full flex-col">
                <div className="mb-6 flex items-center justify-between gap-2">
                    <SearchFilter />

                    <div className="flex-shrink-0">
                        <AnchorLink href={route('organizations.create')}>
                            <span className="md:hidden">{t('Create')}</span>
                            <span className="hidden md:inline">{t('Create Organization')}</span>
                        </AnchorLink>
                    </div>
                </div>

                <TableContainer>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('City')}</TableHead>
                                <TableHead>{t('Phone')}</TableHead>
                                <TableHead className="w-[50px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map(({ id, name, city, phone, deleted_at }) => (
                                <TableRow key={id}>
                                    <TableCell className="relative p-2">
                                        <div className="absolute inset-0 z-10">
                                            <Link href={route('organizations.edit', id)} prefetch className="block h-full w-full">
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
                                            <Link href={route('organizations.edit', id)} prefetch tabIndex={-1} className="block h-full w-full">
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{city}</div>
                                    </TableCell>
                                    <TableCell className="relative p-2">
                                        <div className="absolute inset-0 z-10">
                                            <Link href={route('organizations.edit', id)} prefetch tabIndex={-1} className="block h-full w-full">
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{phone}</div>
                                    </TableCell>
                                    <TableCell className="w-px">
                                        <Button asChild variant="ghost" size="icon">
                                            <Link tabIndex={-1} href={route('organizations.edit', id)} prefetch>
                                                <ChevronRight className="text-muted-foreground h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center">
                                        {t('No organizations found.')}
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
