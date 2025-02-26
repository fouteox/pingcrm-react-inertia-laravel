import clsx from 'clsx';
import React, { ButtonHTMLAttributes, ReactNode } from 'react';

interface SecondaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    type?: 'button' | 'submit' | 'reset';
    className?: string;
    processing?: boolean;
    children: ReactNode;
    onClick?: () => void;
}

const SecondaryButton: React.FC<SecondaryButtonProps> = ({ type = 'button', className = '', processing = false, children, onClick, ...props }) => {
    return (
        <button
            type={type}
            onClick={onClick}
            className={clsx(
                'inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2',
                'text-xs font-semibold tracking-widest text-gray-700 uppercase',
                'shadow-sm transition duration-150 ease-in-out',
                'hover:bg-gray-50',
                'focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden',
                'disabled:opacity-25',
                { 'opacity-25': processing },
                className,
            )}
            disabled={processing}
            {...props}
        >
            {children}
        </button>
    );
};

export default SecondaryButton;
