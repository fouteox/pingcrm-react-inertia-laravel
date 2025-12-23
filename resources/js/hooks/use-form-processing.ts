import { useProcessingContext } from '@/contexts/processing-context';
import { useEffect } from 'react';
import { useMinDuration } from './use-min-duration';

/**
 * Hook that combines minimum duration processing state with global context.
 * Automatically syncs the processing state to the ProcessingContext for
 * components like FlashMessages that need to wait for processing to complete.
 */
export function useFormProcessing(processing: boolean, minDuration?: number): boolean {
    const { setProcessing } = useProcessingContext();
    const isProcessing = useMinDuration(processing, minDuration);

    useEffect(() => {
        setProcessing(isProcessing);
    }, [isProcessing, setProcessing]);

    return isProcessing;
}
