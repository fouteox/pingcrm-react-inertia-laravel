import { useProcessingContext } from '@/contexts/processing-context';
import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface FormLabelProps {
    children: ReactNode;
    htmlFor?: string;
    error?: string;
    className?: string;
}

export function FormLabel({ children, htmlFor, error, className }: FormLabelProps) {
    const { isProcessing } = useProcessingContext();
    const showError = error && !isProcessing;

    return (
        <label
            htmlFor={htmlFor}
            className={cn(
                'mb-2 block text-sm font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                showError && 'text-destructive',
                className,
            )}
        >
            {children}
        </label>
    );
}
