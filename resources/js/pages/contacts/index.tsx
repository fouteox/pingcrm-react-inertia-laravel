import AnchorLink from "@/components/anchor-link";
import { BreadcrumbItem, Contact, PageProps, PaginatedData } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import { ChevronRight, Trash } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { TableContainer } from "@/components/table-container";
import { Button } from "@/components/ui/button";
import InertiaPagination from '@/components/inertia-pagination';
import SearchFilter from '@/components/search-filter';

interface IndexPageProps extends PageProps {
    contacts: PaginatedData<Contact>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Contact",
        count: 2,
        href: route("contacts.index"),
    },
];

export default function Index() {
    const { t } = useTranslation();

    const { contacts } = usePage<IndexPageProps>().props;
    const {
        data,
        meta: { links },
    } = contacts;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t("Contact", { count: 2 })} />

            <div className="w-full h-full flex flex-col">
                <div className="mb-6 flex items-center gap-2 justify-between">
                    <SearchFilter />

                    <div className="flex-shrink-0">
                        <AnchorLink href={route("contacts.create")}>
                            <span className="md:hidden">{t("Create")}</span>
                            <span className="hidden md:inline">{t("Create Contact")}</span>
                        </AnchorLink>
                    </div>
                </div>

                <TableContainer>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t("Name")}</TableHead>
                                <TableHead>{t("Organization", { count: 1 })}</TableHead>
                                <TableHead>{t("City")}</TableHead>
                                <TableHead>{t("Phone")}</TableHead>
                                <TableHead className="w-[50px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map(({ id, name, city, phone, organization, deleted_at }) => (
                                <TableRow key={id}>
                                    <TableCell className="p-2 relative">
                                        <div className="absolute inset-0 z-10">
                                            <Link
                                                href={route("contacts.edit", id)}
                                                prefetch
                                                className="block w-full h-full"
                                            >
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="whitespace-nowrap overflow-hidden text-ellipsis max-w-full relative z-0">
                                            {name}
                                            {deleted_at && (
                                                <Trash className="ml-2 h-3 w-3 shrink-0 text-muted-foreground" />
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="p-2 relative">
                                        <div className="absolute inset-0 z-10">
                                            <Link
                                                href={route("contacts.edit", id)}
                                                prefetch
                                                tabIndex={1}
                                                className="block w-full h-full"
                                            >
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="whitespace-nowrap overflow-hidden text-ellipsis max-w-full relative z-0">
                                            {organization ? organization.name : ""}
                                        </div>
                                    </TableCell>
                                    <TableCell className="p-2 relative">
                                        <div className="absolute inset-0 z-10">
                                            <Link
                                                href={route("contacts.edit", id)}
                                                prefetch
                                                tabIndex={-1}
                                                className="block w-full h-full"
                                            >
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="whitespace-nowrap overflow-hidden text-ellipsis max-w-full relative z-0">
                                            {city}
                                        </div>
                                    </TableCell>
                                    <TableCell className="p-2 relative">
                                        <div className="absolute inset-0 z-10">
                                            <Link
                                                href={route("contacts.edit", id)}
                                                prefetch
                                                tabIndex={-1}
                                                className="block w-full h-full"
                                            >
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="whitespace-nowrap overflow-hidden text-ellipsis max-w-full relative z-0">
                                            {phone}
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-px">
                                        <Button asChild variant="ghost" size="icon">
                                            <Link
                                                tabIndex={-1}
                                                href={route("contacts.edit", id)}
                                                prefetch
                                            >
                                                <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-24 text-center">
                                        {t("No contacts found.")}
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
