import { useSidebar } from '@/components/ui/sidebar';
import { Link, type InertiaLinkProps } from '@inertiajs/react';
import React from 'react';

export function MobileAwareLink({ children, onClick, ...props }: InertiaLinkProps) {
    const { isMobile, setOpenMobile } = useSidebar();

    const handleClick: React.MouseEventHandler = (event) => {
        if (isMobile) {
            setOpenMobile(false);
        }

        if (onClick) {
            onClick(event);
        }
    };

    return (
        <Link {...props} onClick={handleClick}>
            {children}
        </Link>
    );
}
