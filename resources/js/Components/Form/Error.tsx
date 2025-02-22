import clsx from 'clsx';
import React, { ReactNode } from 'react';

interface ErrorProps {
    message?: ReactNode;
    className?: string;
}

export const Error: React.FC<ErrorProps> = ({ message, className = '' }) => {
    if (!message) return null;

    return (
        <div
            className={clsx('mt-2 text-sm text-red-700', className)}
            role="alert"
        >
            {message}
        </div>
    );
};
