import { initServerI18n } from '@/i18n-ssr';
import { PropsWithChildren } from 'react';
import { I18nextProvider } from 'react-i18next';

interface ServerI18nProviderProps {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    initialI18nStore: Record<string, any>;
    initialLanguage: string;
}

export const ServerI18nProvider = ({
    children,
    initialI18nStore,
    initialLanguage,
}: PropsWithChildren<ServerI18nProviderProps>) => {
    const i18nInstance = initServerI18n(initialI18nStore, initialLanguage);

    return <I18nextProvider i18n={i18nInstance}>{children}</I18nextProvider>;
};
