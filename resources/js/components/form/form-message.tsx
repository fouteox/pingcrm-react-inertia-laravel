import { useProcessingContext } from '@/contexts/processing-context';
import { cn } from '@/lib/utils';

interface FormMessageProps {
    error?: string;
    className?: string;
}

export function FormMessage({ error, className }: FormMessageProps) {
    const { isProcessing } = useProcessingContext();

    if (!error || isProcessing) return null;

    return (
        <p className={cn('h-5 -mb-5 text-sm font-medium text-destructive', className)}>
            {error}
        </p>
    );
}
