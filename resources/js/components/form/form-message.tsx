import { cn } from '@/lib/utils';

interface FormMessageProps {
    error?: string;
    className?: string;
}

export function FormMessage({ error, className }: FormMessageProps) {
    if (!error) return null;

    return <p className={cn('text-destructive text-sm font-medium', className)}>{error}</p>;
}
