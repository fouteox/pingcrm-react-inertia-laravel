import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type VariantProps } from 'class-variance-authority';
import { ReactNode } from 'react';

type LinkType = 'btn' | 'anchor';

interface AnchorLinkProps {
    href: string;
    linkType?: LinkType;
    children: ReactNode;
    className?: string;
    variant?: VariantProps<typeof buttonVariants>['variant'];
    size?: VariantProps<typeof buttonVariants>['size'];
}

const AnchorLink = ({ href, linkType = 'btn', className, variant = 'default', size = 'default', children }: AnchorLinkProps) => {
    if (linkType === 'anchor') {
        return (
            <Link href={href} className={cn('text-muted-foreground hover:text-foreground text-sm', className)}>
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
                className,
            )}
        >
            {children}
        </Link>
    );
};

export default AnchorLink;
