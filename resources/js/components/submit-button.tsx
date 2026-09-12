import { Check, Loader2 } from 'lucide-react';
import type { ComponentProps } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';

interface SubmitButtonProps extends Omit<ComponentProps<typeof Button>, 'type'> {
    processing: boolean;
    recentlySuccessful?: boolean;
}

export function SubmitButton({ processing, recentlySuccessful = false, disabled, children, ...props }: SubmitButtonProps) {
    const { t } = useTranslation();

    return (
        <div className="flex items-center gap-3">
            <span role="status" className="text-sm text-muted-foreground">
                {recentlySuccessful && !processing && t('Saved.')}
            </span>
            <Button type="submit" disabled={disabled || processing} {...props}>
                {processing ? <Loader2 className="size-4 animate-spin" /> : recentlySuccessful ? <Check className="size-4" /> : null}
                {children}
            </Button>
        </div>
    );
}
