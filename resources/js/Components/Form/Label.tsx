import clsx from 'clsx';
import React, { ReactNode } from 'react';

interface LabelProps {
    forInput?: string;
    className?: string;
    value?: ReactNode;
    children?: ReactNode;
}

export const Label: React.FC<LabelProps> = ({
    forInput,
    className = '',
    value,
    children,
}) => {
    return (
        <label
            htmlFor={forInput}
            className={clsx('mb-1 block text-gray-700 select-none', className)}
        >
            {value || children}
        </label>
    );
};
