import { Link } from "@inertiajs/react";
import clsx from "clsx";

export default function Pagination({ links = [] }) {
    /**
     * If there are only 3 links, it means there are no previous or next pages.
     * So, we don't need to render the pagination.
     */
    if (links.length === 3) return null;
    return (
        <div className="flex flex-wrap mt-6 -mb-1">
            {links.map(({ active, label, url }) => {
                return url === null ? (
                    <PageInactive key={label} label={label} />
                ) : (
                    <PaginationItem
                        key={label}
                        label={label}
                        active={active}
                        url={url}
                    />
                );
            })}
        </div>
    );
};

const PaginationItem = ({ active, label, url }) => {
    const className = clsx(
        [
            "mr-1 mb-1",
            "px-4 py-3",
            "border border-solid border-gray-300 rounded",
            "text-sm",
            "hover:bg-white",
            "focus:outline-none focus:border-indigo-700 focus:text-indigo-700",
        ],
        {
            "bg-white": active,
        }
    );
    return (
        <Link className={className} href={url}>
            <span dangerouslySetInnerHTML={{ __html: label }}></span>
        </Link>
    );
};

const PageInactive = ({ label }) => {
    const className = clsx(
        "mr-1 mb-1 px-4 py-3 text-sm border rounded border-solid border-gray-300 text-gray-400"
    );

    /**
     * Note: In general you should be aware when using `dangerouslySetInnerHTML`.
     *
     * In this case, `label` from the API is a string, so it's safe to use it.
     * It will be either `&laquo; Previous` or `Next &raquo;`
     */
    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: label }}
        />
    );
};
