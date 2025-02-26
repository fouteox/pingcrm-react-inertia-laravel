import React, { ReactNode, FormEvent } from 'react';
import { cn } from '@/lib/utils';

interface FormProps {
    children: ReactNode;
    onSubmit: (e: FormEvent<HTMLFormElement>) => void;
    className?: string;
}

export function Form({ children, onSubmit, className }: FormProps) {
    return (
        <form onSubmit={onSubmit} className={cn("space-y-4", className)}>
            {children}
        </form>
    );
}
