import { type HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({ message, className = '', ...props }: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p role="alert" {...props} className={cn('text-sm text-destructive', className)}>
            {message}
        </p>
    ) : null;
}
