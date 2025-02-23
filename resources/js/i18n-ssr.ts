import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const initServerI18n = (resources: Record<string, any>, lng: string) => {
    const i18nInstance = i18n.createInstance();

    void i18nInstance.use(initReactI18next).init({
        supportedLngs: ['en', 'fr'],
        fallbackLng: 'en',
        lng,
        resources,
        interpolation: {
            escapeValue: false,
        },
    });

    return i18nInstance;
};
