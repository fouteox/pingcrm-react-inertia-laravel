import clsx from 'clsx';
import React, { ChangeEvent } from 'react';

interface CheckboxProps {
    name: string;
    value?: boolean | string;
    handleChange: (e: ChangeEvent<HTMLInputElement>) => void;
    className?: string;
    disabled?: boolean;
}

const Checkbox: React.FC<CheckboxProps> = ({
    name,
    value,
    handleChange,
    className = '',
    disabled = false,
}) => {
    return (
        <input
            type="checkbox"
            name={name}
            checked={!!value}
            className={clsx(
                'rounded-sm border-gray-300 text-indigo-600 shadow-xs',
                'focus:ring-indigo-500',
                'disabled:opacity-50',
                className,
            )}
            onChange={handleChange}
            disabled={disabled}
        />
    );
};

export default Checkbox;
