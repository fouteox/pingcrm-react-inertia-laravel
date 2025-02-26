import { Link } from "@inertiajs/react";
import { cn } from "@/lib/utils";
import { buttonVariants, type ButtonProps } from "@/components/ui/button";
import { ReactNode } from "react";

type LinkType = "btn" | "anchor";

interface AnchorLinkProps {
    href: string;
    linkType?: LinkType;
    children: ReactNode;
    className?: string;
    variant?: ButtonProps["variant"];
    size?: ButtonProps["size"];
}

const AnchorLink = ({
                        href,
                        linkType = "btn",
                        className,
                        variant = "default",
                        size = "default",
                        children,
                    }: AnchorLinkProps) => {
    if (linkType === "anchor") {
        return (
            <Link
                href={href}
                className={cn(
                    "text-sm text-muted-foreground hover:text-foreground",
                    className
                )}
            >
                {children}
            </Link>
        );
    }

    return (
        <Link
            href={href}
            className={cn(
                buttonVariants({
                    variant,
                    size,
                }),
                className
            )}
        >
            {children}
        </Link>
    );
};

export default AnchorLink;
