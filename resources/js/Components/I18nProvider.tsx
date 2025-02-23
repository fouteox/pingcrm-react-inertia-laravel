import { PropsWithChildren } from 'react';
import { ClientI18nProvider } from './ClientI18nProvider';
import { ServerI18nProvider } from './ServerI18nProvider';

interface I18nProviderProps {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    initialI18nStore: Record<string, any>;
    initialLanguage: string;
}

export const I18nProvider = ({
    children,
    initialI18nStore,
    initialLanguage,
}: PropsWithChildren<I18nProviderProps>) => {
    if (import.meta.env.SSR) {
        return (
            <ServerI18nProvider
                initialI18nStore={initialI18nStore}
                initialLanguage={initialLanguage}
            >
                {children}
            </ServerI18nProvider>
        );
    }

    return (
        <ClientI18nProvider
            initialI18nStore={initialI18nStore}
            initialLanguage={initialLanguage}
        >
            {children}
        </ClientI18nProvider>
    );
};
