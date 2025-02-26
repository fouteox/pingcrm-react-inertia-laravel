import React from 'react';
import { cn } from '@/lib/utils';

interface FormMessageProps {
    error?: string;
    className?: string;
}

export function FormMessage({
                                error,
                                className
                            }: FormMessageProps) {
    if (!error) return null;

    return (
        <p className={cn("text-sm font-medium text-destructive", className)}>
            {error}
        </p>
    );
}
