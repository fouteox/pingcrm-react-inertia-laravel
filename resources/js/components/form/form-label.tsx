import React, { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface FormLabelProps {
    children: ReactNode;
    htmlFor?: string;
    error?: string;
    className?: string;
}

export function FormLabel({
                              children,
                              htmlFor,
                              error,
                              className
                          }: FormLabelProps) {
    return (
        <label
            htmlFor={htmlFor}
            className={cn(
                "text-sm font-medium block mb-2 peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
                error && "text-destructive",
                className
            )}
        >
            {children}
        </label>
    );
}
