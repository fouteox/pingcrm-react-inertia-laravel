import React, { ReactNode } from 'react';
import { Error } from './Error';
import { Label } from './Label';

interface FieldProps {
    label?: string;
    value?: string;
    errors?: string;
    children: ReactNode;
    className?: string;
}

export const Field: React.FC<FieldProps> = ({
    label,
    value,
    errors = null,
    children,
    className = '',
}) => {
    const errorMessage = Array.isArray(errors) ? errors[0] : errors;

    return (
        <div className={`w-full pr-6 pb-7 lg:w-1/2 ${className}`}>
            {(label || value) && <Label forInput={label} value={value} />}
            {children}
            <Error message={errorMessage} />
        </div>
    );
};
