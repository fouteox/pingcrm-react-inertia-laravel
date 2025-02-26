import clsx from 'clsx';
import React, { ReactNode, SelectHTMLAttributes } from 'react';

interface SelectInputProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'className'> {
    name: string;
    className?: string;
    children: ReactNode;
    containerClassName?: string;
    label?: string;
}

const SelectInput: React.FC<SelectInputProps> = ({ name, className = '', containerClassName = '', children, ...props }) => {
    return (
        <div className={containerClassName}>
            <select
                id={name}
                name={name}
                className={clsx(
                    'relative block w-full appearance-none rounded-sm border border-gray-200',
                    'bg-white p-2 text-left font-sans leading-normal text-gray-700',
                    'focus:ring-3 focus:ring-indigo-400/50',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
        </div>
    );
};

export default SelectInput;
