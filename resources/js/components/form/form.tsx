import { cn } from '@/lib/utils';
import { FormEvent, ReactNode } from 'react';

interface FormProps {
    children: ReactNode;
    onSubmit: (e: FormEvent<HTMLFormElement>) => void;
    className?: string;
}

export function Form({ children, onSubmit, className }: FormProps) {
    return (
        <form onSubmit={onSubmit} className={cn('space-y-4', className)}>
            {children}
        </form>
    );
}
