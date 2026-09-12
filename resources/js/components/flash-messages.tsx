import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';

export default function FlashMessages() {
    const { t } = useTranslation();

    useEffect(
        () =>
            router.on('flash', ({ detail: { flash } }) => {
                if (flash.success) {
                    toast.success(flash.success);
                }
                if (flash.error) {
                    toast.error(flash.error);
                }
            }),
        [],
    );

    useEffect(
        () =>
            router.on('error', ({ detail: { errors } }) => {
                toast.error(t('form_errors', { count: Object.keys(errors).length }));
            }),
        [t],
    );

    return <Toaster />;
}
