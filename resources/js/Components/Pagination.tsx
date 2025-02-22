import { Link } from '@inertiajs/react';
import clsx from 'clsx';
import React from 'react';

interface PaginationLink {
    active: boolean;
    label: string;
    url: string | null;
}

interface PaginationProps {
    links: PaginationLink[];
}

interface PaginationItemProps {
    active: boolean;
    label: string;
    url: string;
}

interface PageInactiveProps {
    label: string;
}

const PaginationItem: React.FC<PaginationItemProps> = ({
    active,
    label,
    url,
}) => {
    const className = clsx(
        [
            'mr-1 mb-1',
            'px-4 py-3',
            'border border-solid border-gray-300 rounded-sm',
            'text-sm',
            'hover:bg-white',
            'focus:outline-hidden focus:border-indigo-700 focus:text-indigo-700',
        ],
        {
            'bg-white': active,
        },
    );

    return (
        <Link className={className} href={url}>
            <span dangerouslySetInnerHTML={{ __html: label }} />
        </Link>
    );
};

const PageInactive: React.FC<PageInactiveProps> = ({ label }) => {
    const className = clsx(
        'mr-1 mb-1 px-4 py-3 text-sm border rounded-sm border-solid border-gray-300 text-gray-400',
    );

    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: label }}
        />
    );
};

const Pagination: React.FC<PaginationProps> = ({ links = [] }) => {
    /**
     * If there are only 3 links, it means there are no previous or next pages.
     * So, we don't need to render the pagination.
     */
    if (links.length === 3) return null;

    return (
        <div className="mt-6 -mb-1 flex flex-wrap">
            {links.map(({ active, label, url }, index) => {
                return url === null ? (
                    <PageInactive
                        key={`page-inactive-${index}`}
                        label={label}
                    />
                ) : (
                    <PaginationItem
                        key={`page-active-${index}`}
                        label={label}
                        active={active}
                        url={url}
                    />
                );
            })}
        </div>
    );
};

export default Pagination;
