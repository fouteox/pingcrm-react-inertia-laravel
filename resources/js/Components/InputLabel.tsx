import clsx from 'clsx';
import React, { ReactNode } from 'react';

interface InputLabelProps {
    forInput?: string;
    value?: string;
    className?: string;
    children?: ReactNode;
}

const InputLabel: React.FC<InputLabelProps> = ({
    forInput,
    value,
    className = '',
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

export default InputLabel;
