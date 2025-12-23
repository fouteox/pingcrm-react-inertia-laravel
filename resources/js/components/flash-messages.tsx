import { Toaster } from '@/components/ui/sonner';
import { useProcessingContext } from '@/contexts/processing-context';
import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';

interface FlashMessage {
    success?: string;
    error?: string;
}

interface FlashPageProps extends SharedData {
    flash: FlashMessage;
    errors: Record<string, string[]>;
    [key: string]: unknown;
}

export default function FlashMessages() {
    const { t } = useTranslation();
    const { flash, errors } = usePage<FlashPageProps>().props;
    const { isProcessing, setShowSuccess } = useProcessingContext();
    const numOfErrors = Object.keys(errors).length;

    const hasPendingVisitRef = useRef(false);
    const isPopstateRef = useRef(false);
    const wasProcessingRef = useRef(false);
    const pendingFlashRef = useRef<{ flash: FlashMessage; numOfErrors: number; wasFormSubmit: boolean } | null>(null);

    // Capture when processing starts (persists even if isProcessing becomes false before flash is stored)
    useEffect(() => {
        if (isProcessing) {
            wasProcessingRef.current = true;
        }
    }, [isProcessing]);

    // Track navigation events
    useEffect(() => {
        const handlePopstate = () => {
            isPopstateRef.current = true;
        };
        window.addEventListener('popstate', handlePopstate);

        const unregister = router.on('before', () => {
            if (!isPopstateRef.current) {
                hasPendingVisitRef.current = true;
            }
            isPopstateRef.current = false;
        });

        return () => {
            window.removeEventListener('popstate', handlePopstate);
            unregister();
        };
    }, []);

    // Store and show flash messages
    useEffect(() => {
        const isPending = hasPendingVisitRef.current;
        const isPopstate = isPopstateRef.current;
        const hasFlash = flash.success || flash.error || numOfErrors > 0;

        // Store flash if this is a user-initiated visit with a flash message
        if (isPending && !isPopstate && hasFlash) {
            hasPendingVisitRef.current = false;
            isPopstateRef.current = false;
            pendingFlashRef.current = { flash: { ...flash }, numOfErrors, wasFormSubmit: wasProcessingRef.current };
        }

        // Show toast when not processing and we have a pending flash
        if (!isProcessing && pendingFlashRef.current) {
            const { flash: pendingFlash, numOfErrors: pendingNumOfErrors, wasFormSubmit } = pendingFlashRef.current;
            pendingFlashRef.current = null;
            wasProcessingRef.current = false;

            if (pendingFlash.success) {
                // Show success in button only for form submissions, otherwise use toast
                if (wasFormSubmit) {
                    setShowSuccess(true);
                } else {
                    toast.success(pendingFlash.success);
                }
            }

            if (pendingFlash.error) {
                toast.error(pendingFlash.error);
            } else if (pendingNumOfErrors > 0) {
                toast.error(t('form_errors', { count: pendingNumOfErrors }));
            }
        }
    }, [flash, errors, numOfErrors, isProcessing, t, setShowSuccess]);

    return <Toaster />;
}
