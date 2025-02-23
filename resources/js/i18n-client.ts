import i18n from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import HttpBackend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

export const initClientI18n = () => {
    const i18nInstance = i18n.createInstance();

    void i18nInstance
        .use(LanguageDetector)
        .use(HttpBackend)
        .use(initReactI18next)
        .init({
            supportedLngs: ['en', 'fr'],
            fallbackLng: 'en',
            interpolation: {
                escapeValue: false,
            },
        });

    return i18nInstance;
};
