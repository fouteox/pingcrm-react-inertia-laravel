import AnchorLink from "@/components/anchor-link";
import { BreadcrumbItem, PageProps, PaginatedData, User } from "@/types";
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
    users: PaginatedData<User>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "User",
        count: 2,
        href: route("users.index"),
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
            <Head title={t("User", { count: 2 })} />

            <div className="w-full h-full flex flex-col">
                <div className="mb-6 flex items-center gap-2 justify-between">
                    <SearchFilter />

                    <div className="flex-shrink-0">
                        <AnchorLink href={route("users.create")}>
                            <span className="md:hidden">{t("Create")}</span>
                            <span className="hidden md:inline">{t("Create User")}</span>
                        </AnchorLink>
                    </div>
                </div>

                <TableContainer>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t("Name")}</TableHead>
                                <TableHead>{t("Email")}</TableHead>
                                <TableHead>{t("Role")}</TableHead>
                                <TableHead className="w-[50px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map(({ id, name, email, owner, deleted_at }) => (
                                <TableRow key={id}>
                                    <TableCell className="p-2 relative">
                                        <div className="absolute inset-0 z-10">
                                            <Link
                                                href={route("users.edit", id)}
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
                                                href={route("users.edit", id)}
                                                prefetch
                                                tabIndex={-1}
                                                className="block w-full h-full"
                                            >
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="whitespace-nowrap overflow-hidden text-ellipsis max-w-full relative z-0">
                                            {email}
                                        </div>
                                    </TableCell>
                                    <TableCell className="p-2 relative">
                                        <div className="absolute inset-0 z-10">
                                            <Link
                                                href={route("users.edit", id)}
                                                prefetch
                                                tabIndex={-1}
                                                className="block w-full h-full"
                                            >
                                                <span className="sr-only">Modifier {name}</span>
                                            </Link>
                                        </div>
                                        <div className="whitespace-nowrap overflow-hidden text-ellipsis max-w-full relative z-0">
                                            {owner ? t("Owner") : t("User")}
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-px">
                                        <Button asChild variant="ghost" size="icon">
                                            <Link
                                                tabIndex={-1}
                                                href={route("users.edit", id)}
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
                                    <TableCell colSpan={4} className="h-24 text-center">
                                        {t("No users found.")}
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
