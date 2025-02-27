import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface FormLabelProps {
    children: ReactNode;
    htmlFor?: string;
    error?: string;
    className?: string;
}

export function FormLabel({ children, htmlFor, error, className }: FormLabelProps) {
    return (
        <label
            htmlFor={htmlFor}
            className={cn(
                'mb-2 block text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                error && 'text-destructive',
                className,
            )}
        >
            {children}
        </label>
    );
}
