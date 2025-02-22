import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef } from 'react';
import toast, { Toaster } from 'react-hot-toast';

interface FlashMessage {
    success?: string;
    error?: string;
}

interface FlashPageProps extends PageProps {
    flash: FlashMessage;
    errors: Record<string, string[]>;
    [key: string]: unknown;
}

export default function FlashMessages() {
    const { flash, errors } = usePage<FlashPageProps>().props;
    const numOfErrors = Object.keys(errors).length;

    const currentNavId = useRef<string | null>(null);
    const lastShownNavId = useRef<string | null>(null);

    const handleNavigationStart = useCallback(() => {
        currentNavId.current = Date.now().toString();
    }, []);

    useEffect(() => {
        const removeStartEventListener = router.on(
            'start',
            handleNavigationStart,
        );

        return () => {
            removeStartEventListener();
        };
    }, [handleNavigationStart]);

    useEffect(() => {
        if (currentNavId.current !== lastShownNavId.current) {
            if (flash.success) {
                toast.success(flash.success);
            }

            if (flash.error || numOfErrors > 0) {
                toast.error(
                    numOfErrors === 1
                        ? 'There is one form error'
                        : `There are ${numOfErrors} form errors.`,
                );
            }

            lastShownNavId.current = currentNavId.current;
        }
    }, [flash, errors, numOfErrors]);

    return <Toaster />;
}
