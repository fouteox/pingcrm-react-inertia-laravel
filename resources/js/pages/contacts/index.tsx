import AnchorLink from '@/components/anchor-link';
import InertiaPagination from '@/components/inertia-pagination';
import SearchFilter from '@/components/search-filter';
import { TableContainer } from '@/components/table-container';
import { Button } from '@/components/ui/button';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePageActions } from '@/contexts/page-context';
import contacts from '@/routes/contacts';
import { BreadcrumbItem, Contact, PaginatedData, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight, Trash } from 'lucide-react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

interface IndexPageProps extends SharedData {
    contacts: PaginatedData<Contact>;
}

export default function Index() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Contact',
                count: 2,
                href: contacts.index().url,
            },
        ],
        [],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    const { contacts: contactsData } = usePage<IndexPageProps>().props;
    const {
        data,
        meta: { links },
    } = contactsData;

    return (
        <>
            <Head title={t('Contact', { count: 2 })} />

            <div className="flex h-full w-full flex-col">
                <div className="mb-6 flex items-center justify-between gap-2">
                    <SearchFilter />

                    <div className="flex-shrink-0">
                        <AnchorLink href={contacts.create().url}>
                            <span className="md:hidden">{t('Create')}</span>
                            <span className="hidden md:inline">{t('Create Contact')}</span>
                        </AnchorLink>
                    </div>
                </div>

                <TableContainer>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('Name')}</TableHead>
                            <TableHead>{t('Organization', { count: 1 })}</TableHead>
                            <TableHead>{t('City')}</TableHead>
                            <TableHead>{t('Phone')}</TableHead>
                            <TableHead className="w-[50px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.map(({ id, name, city, phone, organization, deleted_at }) => (
                            <TableRow key={id}>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch className="block h-full w-full">
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
                                        <Link href={contacts.edit(id)} prefetch tabIndex={1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
                                        {organization ? organization.name : ''}
                                    </div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{city}</div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">Modifier {name}</span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{phone}</div>
                                </TableCell>
                                <TableCell className="w-px">
                                    <Button asChild variant="ghost" size="icon">
                                        <Link tabIndex={-1} href={contacts.edit(id)} prefetch>
                                            <ChevronRight className="text-muted-foreground h-4 w-4" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={5} className="h-24 text-center">
                                    {t('No contacts found.')}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </TableContainer>

                <div className="my-6">
                    <InertiaPagination links={links} />
                </div>
            </div>
        </>
    );
}
