import clsx from 'clsx';
import React, { ReactNode } from 'react';

interface InputErrorProps {
    message?: ReactNode;
    className?: string;
}

const InputError: React.FC<InputErrorProps> = ({ message, className = '' }) => {
    if (!message) return null;

    return (
        <div
            className={clsx('mt-2 text-sm text-red-700', className)}
            role="alert"
            aria-live="polite"
        >
            {message}
        </div>
    );
};

export default InputError;
