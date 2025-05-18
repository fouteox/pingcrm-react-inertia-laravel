import { Toaster } from '@/components/ui/sonner';
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

interface NavigationState {
    currentVisitId: string;
    lastShownVisitId: string | null;
}

type NotificationHandler = (message: string) => void;

export default function FlashMessages() {
    const { t } = useTranslation();
    const { flash, errors } = usePage<FlashPageProps>().props;
    const numOfErrors = Object.keys(errors).length;

    const navigationState = useRef<NavigationState>({
        currentVisitId: `initial-${Date.now().toString()}`,
        lastShownVisitId: null,
    });

    useEffect(() => {
        const { currentVisitId, lastShownVisitId } = navigationState.current;

        if (currentVisitId !== lastShownVisitId) {
            const notifications: [string | undefined | false, NotificationHandler][] = [
                [flash.success, toast.success],
                [flash.error || (numOfErrors > 0 && t('form_errors', { count: numOfErrors })), toast.error],
            ];

            notifications.forEach(([message, handler]) => {
                if (message) {
                    handler(message);
                }
            });

            navigationState.current.lastShownVisitId = currentVisitId;
        }

        const unregisterSuccess = router.on('success', () => {
            navigationState.current.currentVisitId = `visit-${Date.now().toString()}`;
        });

        return () => {
            unregisterSuccess();
        };
    }, [flash, errors, numOfErrors, t]);

    return <Toaster />;
}
