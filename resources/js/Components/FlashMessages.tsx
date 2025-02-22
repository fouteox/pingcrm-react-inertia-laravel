import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef } from 'react';
import toast, { Toaster } from 'react-hot-toast';
import { useTranslation } from 'react-i18next';

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
    const { t } = useTranslation();

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
                toast.error(t('form_errors', { count: numOfErrors }));
            }

            lastShownNavId.current = currentNavId.current;
        }
    }, [flash, errors, numOfErrors, t]);

    return <Toaster />;
}
