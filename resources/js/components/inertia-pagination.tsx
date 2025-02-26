import * as React from "react";
import { Link } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import { cn } from "@/lib/utils";
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationEllipsis,
} from "@/components/ui/pagination";
import { buttonVariants } from "@/components/ui/button";
import { ChevronLeft, ChevronRight } from "lucide-react";

interface InertiaLinkType {
    url: string | null;
    label: string;
    active: boolean;
}

interface InertiaPaginationProps {
    links: InertiaLinkType[];
    className?: string;
}

export default function InertiaPagination({ links, className }: InertiaPaginationProps) {
    const { t } = useTranslation();

    // Hide pagination if there's only one page
    if (links.length <= 3) return null;

    // First link is "Previous", last link is "Next"
    const prevLink = links[0];
    const nextLink = links[links.length - 1];

    // Page links are between first and last link
    const pageLinks = links.slice(1, -1);

    // Helper to clean HTML entities in labels
    const cleanLabel = (label: string) => {
        const temp = document.createElement("div");
        temp.innerHTML = label;
        return temp.textContent || temp.innerText || label;
    };

    // Determine which page links to show on mobile
    const activeIndex = pageLinks.findIndex(link => link.active);
    const totalPages = pageLinks.length;

    // If many pages, determine which ones to show on mobile
    const getMobileVisibility = (index: number) => {
        if (totalPages <= 5) return true; // Show all if 5 or fewer

        const mobileStart = Math.max(0, Math.min(activeIndex - 1, totalPages - 3));
        return index >= mobileStart && index < mobileStart + 3;
    };

    return (
        <Pagination className={className}>
            <PaginationContent>
                {/* Previous button */}
                <PaginationItem>
                    {prevLink.url ? (
                        <Link
                            href={prevLink.url}
                            className={cn(
                                buttonVariants({ variant: "ghost", size: "default" }),
                                "gap-1 px-2.5"
                            )}
                            aria-label={t("Go to previous page")}
                        >
                            <ChevronLeft className="h-4 w-4" />
                            <span className="hidden sm:inline">{t("Previous")}</span>
                        </Link>
                    ) : (
                        <span
                            className={cn(
                                buttonVariants({ variant: "ghost", size: "default" }),
                                "pointer-events-none opacity-50 gap-1 px-2.5"
                            )}
                            aria-disabled="true"
                        >
                            <ChevronLeft className="h-4 w-4" />
                            <span className="hidden sm:inline">{t("Previous")}</span>
                        </span>
                    )}
                </PaginationItem>

                {/* Page links */}
                {pageLinks.map((link, i) => (
                    <PaginationItem
                        key={i}
                        className={!getMobileVisibility(i) ? 'hidden sm:block' : undefined}
                    >
                        {link.url ? (
                            <Link
                                href={link.url}
                                className={cn(
                                    buttonVariants({
                                        variant: link.active ? "outline" : "ghost",
                                        size: "icon",
                                    })
                                )}
                                aria-current={link.active ? "page" : undefined}
                                aria-label={`${t("Page")} ${cleanLabel(link.label)}`}
                            >
                                {cleanLabel(link.label)}
                            </Link>
                        ) : link.label.includes("...") ? (
                            <PaginationEllipsis />
                        ) : (
                            <span
                                className={cn(
                                    buttonVariants({
                                        variant: link.active ? "outline" : "ghost",
                                        size: "icon",
                                    }),
                                    "pointer-events-none"
                                )}
                                aria-disabled="true"
                                aria-current={link.active ? "page" : undefined}
                            >
                                {cleanLabel(link.label)}
                            </span>
                        )}
                    </PaginationItem>
                ))}

                {/* Next button */}
                <PaginationItem>
                    {nextLink.url ? (
                        <Link
                            href={nextLink.url}
                            className={cn(
                                buttonVariants({ variant: "ghost", size: "default" }),
                                "gap-1 px-2.5"
                            )}
                            aria-label={t("Go to next page")}
                        >
                            <span className="hidden sm:inline">{t("Next")}</span>
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span
                            className={cn(
                                buttonVariants({ variant: "ghost", size: "default" }),
                                "pointer-events-none opacity-50 gap-1 px-2.5"
                            )}
                            aria-disabled="true"
                        >
                            <span className="hidden sm:inline">{t("Next")}</span>
                            <ChevronRight className="h-4 w-4" />
                        </span>
                    )}
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}
