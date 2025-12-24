import { useProcessingContext } from '@/contexts/processing-context';
import { cn } from '@/lib/utils';
import { InputHTMLAttributes } from 'react';

interface FormInputProps extends InputHTMLAttributes<HTMLInputElement> {
    error?: string;
}

export function FormInput({ className, error, ...props }: FormInputProps) {
    const { isProcessing } = useProcessingContext();
    const showError = error && !isProcessing;

    return (
        <input
            className={cn(
                'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                showError && 'border-destructive',
                className,
            )}
            {...props}
        />
    );
}
