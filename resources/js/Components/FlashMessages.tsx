import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
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

interface NavigationState {
    currentId: string;
    lastShownId: string | null;
}

type NotificationHandler = (message: string) => void;

export default function FlashMessages() {
    const { t } = useTranslation();
    const { flash, errors } = usePage<FlashPageProps>().props;
    const numOfErrors = Object.keys(errors).length;

    const navigationState = useRef<NavigationState>({
        currentId: Date.now().toString(),
        lastShownId: null,
    });

    useEffect(() => {
        const { currentId, lastShownId } = navigationState.current;

        if (currentId !== lastShownId) {
            const notifications: [
                string | undefined | false,
                NotificationHandler,
            ][] = [
                [flash.success, toast.success],
                [
                    flash.error ||
                        (numOfErrors > 0 &&
                            t('form_errors', { count: numOfErrors })),
                    toast.error,
                ],
            ];

            notifications.forEach(([message, handler]) => {
                if (message) {
                    handler(message);
                }
            });

            navigationState.current.lastShownId = currentId;
        }

        return router.on('start', () => {
            navigationState.current.currentId = Date.now().toString();
        });
    }, [flash, errors, numOfErrors, t]);

    return <Toaster />;
}
