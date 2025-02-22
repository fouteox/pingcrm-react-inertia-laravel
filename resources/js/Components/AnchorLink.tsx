import { Link } from '@inertiajs/react';
import clsx from 'clsx';
import { ReactNode } from 'react';

type AnchorStyle = 'btn' | 'anchor';
type ButtonType = 'button' | 'submit' | 'reset';

interface AnchorLinkProps {
    href: string;
    type?: ButtonType;
    style?: AnchorStyle;
    className?: string;
    children: ReactNode;
}

const AnchorLink = ({
    href,
    type = 'button',
    style,
    className,
    children,
}: AnchorLinkProps) => {
    const classStyle = clsx(className, {
        'px-6 py-3 rounded-sm bg-indigo-600 text-white text-sm leading-4 font-bold whitespace-nowrap hover:bg-orange-400 focus:bg-orange-400':
            style === 'btn',
        'ml-3 text-gray-500 hover:text-gray-700 focus:text-indigo-500 text-sm':
            style === 'anchor',
    });

    return (
        <Link href={href} className={classStyle} type={type}>
            {children}
        </Link>
    );
};

export default AnchorLink;
