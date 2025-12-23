import { buttonVariants } from '@/components/ui/button';
import { useProcessingContext } from '@/contexts/processing-context';
import { cn } from '@/lib/utils';
import { type VariantProps } from 'class-variance-authority';
import { Check, Loader2 } from 'lucide-react';
import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';

export interface SubmitButtonProps
    extends Omit<React.ComponentProps<'button'>, 'type'>,
            VariantProps<typeof buttonVariants> {
    processing: boolean;
}

export function SubmitButton({
    processing,
    disabled,
    children,
    className,
    variant,
    size,
    ...props
}: SubmitButtonProps) {
    const { showSuccess, setShowSuccess } = useProcessingContext();
    const buttonRef = useRef<HTMLButtonElement>(null);
    const [measuredWidth, setMeasuredWidth] = useState<number | null>(null);
    const [isMeasuring, setIsMeasuring] = useState(true);
    const [enableTransitions, setEnableTransitions] = useState(false);
    const childrenRef = useRef(children);

    const showSpinner = isMeasuring || processing;
    const showCheck = showSuccess && !processing;

    // Re-measure when children change (e.g., language switch)
    useLayoutEffect(() => {
        if (childrenRef.current !== children) {
            childrenRef.current = children;
            setIsMeasuring(true);
            setMeasuredWidth(null);
            setEnableTransitions(false);
        }
    }, [children]);

    // Measure button width with spinner visible
    useLayoutEffect(() => {
        if (buttonRef.current && isMeasuring) {
            const width = buttonRef.current.getBoundingClientRect().width;
            setMeasuredWidth(width);
            setIsMeasuring(false);
        }
    }, [isMeasuring]);

    // Enable transitions after initial render to avoid flash
    useEffect(() => {
        if (!isMeasuring && !enableTransitions) {
            const timeout = setTimeout(() => setEnableTransitions(true), 0);
            return () => clearTimeout(timeout);
        }
    }, [isMeasuring, enableTransitions]);

    // Reset success when a new submission starts (clears any existing timer)
    useEffect(() => {
        if (processing) {
            setShowSuccess(false);
        }
    }, [processing, setShowSuccess]);

    // Reset success state after 1.5 seconds
    useEffect(() => {
        if (showSuccess) {
            const timeout = setTimeout(() => setShowSuccess(false), 1500);
            return () => clearTimeout(timeout);
        }
    }, [showSuccess, setShowSuccess]);

    return (
        <button
            ref={buttonRef}
            type="submit"
            disabled={disabled || processing}
            className={cn(buttonVariants({ variant, size, className }))}
            style={{
                visibility: isMeasuring ? 'hidden' : 'visible',
                minWidth: measuredWidth ? `${measuredWidth}px` : undefined,
            }}
            {...props}
        >
            <span
                className={cn(
                    'inline-grid items-center',
                    enableTransitions && 'transition-[grid-template-columns,gap,opacity] duration-150 ease-out',
                    showSpinner || showCheck ? 'grid-cols-[1rem_auto] gap-2' : 'grid-cols-[0px_auto] gap-0',
                )}
            >
                <span className={cn('overflow-hidden', showSpinner || showCheck ? 'opacity-100' : 'opacity-0')}>
                    {showCheck ? (
                        <span className="success-burst flex size-4 items-center justify-center bg-green-500 animate-in zoom-in-0 duration-200">
                            <Check className="size-2.5 stroke-3 text-white" />
                        </span>
                    ) : (
                        <Loader2 className="size-4 shrink-0 animate-spin" />
                    )}
                </span>
                <span>{children}</span>
            </span>
        </button>
    );
}
