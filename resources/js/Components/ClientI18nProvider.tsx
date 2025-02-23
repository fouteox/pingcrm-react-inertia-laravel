import { initClientI18n } from '@/i18n-client';
import { PropsWithChildren } from 'react';
import { I18nextProvider, useSSR } from 'react-i18next';

interface ClientI18nProviderProps {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    initialI18nStore: Record<string, any>;
    initialLanguage: string;
}

export const ClientI18nProvider = ({
    children,
    initialI18nStore,
    initialLanguage,
}: PropsWithChildren<ClientI18nProviderProps>) => {
    const i18nInstance = initClientI18n();
    useSSR(initialI18nStore, initialLanguage);

    return <I18nextProvider i18n={i18nInstance}>{children}</I18nextProvider>;
};
