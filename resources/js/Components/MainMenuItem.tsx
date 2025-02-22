import Icon from '@/Components/Icon';
import { Link } from '@inertiajs/react';
import clsx from 'clsx';

interface MainMenuItemProps {
    icon: string;
    link: string;
    text: string;
}

export default function MainMenuItem({ icon, link, text }: MainMenuItemProps) {
    const isActive = route().current(link + '*');

    const iconClasses = clsx('w-4 h-4 mr-2', {
        'text-white fill-current': isActive,
        'text-indigo-400 group-hover:text-white fill-current': !isActive,
    });

    const textClasses = clsx({
        'text-white': isActive,
        'text-indigo-200 group-hover:text-white': !isActive,
    });

    return (
        <div className="mb-2">
            <Link
                href={route(link)}
                className="group flex items-center py-3 text-indigo-300"
            >
                <Icon name={icon} className={iconClasses} />
                <div className={textClasses}>{text}</div>
            </Link>
        </div>
    );
}
