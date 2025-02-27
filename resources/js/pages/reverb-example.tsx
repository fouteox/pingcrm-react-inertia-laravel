import { Button } from '@/components/ui/button';
import { usePageActions } from '@/contexts/page-context';
import { useReverbNotification } from '@/contexts/reverb-context';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { v4 as uuidv4 } from 'uuid';

interface FormData {
    uuid: string;
    [key: string]: string | number | boolean | File | null;
}

export default function ReverbExample() {
    const { t } = useTranslation();
    const { setBreadcrumbs } = usePageActions();

    const breadcrumbs: BreadcrumbItem[] = React.useMemo(
        () => [
            {
                title: 'Reverb Demo',
                href: route('reverb.index'),
            },
        ],
        [],
    );

    useEffect(() => {
        setBreadcrumbs(breadcrumbs);
    }, [breadcrumbs, setBreadcrumbs]);

    const { addUuid } = useReverbNotification();
    const form = useForm<FormData>({
        uuid: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const uuid = uuidv4();
        addUuid(uuid);

        form.transform(() => ({
            uuid,
        }));

        form.post(route('reverb.store'), {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error);
                }
            },
        });
    };

    return (
        <>
            <Head title={t('Reverb Demo')} />

            <h1 className="mb-4 text-xl font-bold">{t('Reverb Demo')}</h1>

            <p className="mb-4">{t('This page demonstrates the use of Reverb for real-time notifications. When you click the button below:')}</p>
            <ul className="mb-4 list-inside list-disc space-y-2">
                <li>{t('A background job will be triggered')}</li>
                <li>{t('You can navigate to other pages')}</li>
                <li>{t('A notification will appear after about 5 seconds')}</li>
            </ul>

            <form onSubmit={handleSubmit}>
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? t('Processing') : t('Send')}
                </Button>
            </form>
        </>
    );
}
