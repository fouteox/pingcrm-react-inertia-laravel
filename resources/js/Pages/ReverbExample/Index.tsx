import Layout from '@/Components/Layout';
import { useReverbNotification } from '@/contexts/ReverbExampleNotificationContext';
import { Head, useForm } from '@inertiajs/react';
import React, { ReactNode } from 'react';
import toast from 'react-hot-toast';
import { useTranslation } from 'react-i18next';
import { v4 as uuidv4 } from 'uuid';

interface FormData {
    uuid: string;
    [key: string]: string | number | boolean | File | null;
}

const Index = () => {
    const { t } = useTranslation();

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

            <div className="max-w-3xl">
                <h1 className="mb-8 text-3xl font-bold">{t('Reverb Demo')}</h1>

                <div className="mb-6 rounded bg-white p-4 shadow">
                    <p className="mb-4">
                        {t(
                            'This page demonstrates the use of Reverb for real-time notifications. When you click the button below:',
                        )}
                    </p>
                    <ul className="mb-4 list-inside list-disc space-y-2">
                        <li>{t('A background job will be triggered')}</li>
                        <li>{t('You can navigate to other pages')}</li>
                        <li>
                            {t(
                                'A notification will appear after about 5 seconds',
                            )}
                        </li>
                    </ul>
                </div>

                <form onSubmit={handleSubmit}>
                    <button
                        type="submit"
                        className="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:opacity-50"
                        disabled={form.processing}
                    >
                        {form.processing ? t('Processing') : t('Send')}
                    </button>
                </form>
            </div>
        </>
    );
};

Index.layout = (page: ReactNode): ReactNode => <Layout>{page}</Layout>;

export default Index;
