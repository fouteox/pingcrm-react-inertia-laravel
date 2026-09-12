import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemData } from '@/types';

export function Breadcrumbs({ breadcrumbs }: { breadcrumbs: BreadcrumbItemData[] }) {
    const { t } = useTranslation();

    return (
        <>
            {breadcrumbs.length > 0 && (
                <Breadcrumb>
                    <BreadcrumbList>
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            return (
                                <Fragment key={index}>
                                    <BreadcrumbItem>
                                        {isLast ? (
                                            <BreadcrumbPage>
                                                {t(item.title, item.count !== undefined ? { count: item.count } : undefined)}
                                            </BreadcrumbPage>
                                        ) : (
                                            <BreadcrumbLink render={<Link href={item.href} />}>
                                                {t(item.title, item.count !== undefined ? { count: item.count } : undefined)}
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && <BreadcrumbSeparator />}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
