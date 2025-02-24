import i18n from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import HttpBackend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

let i18nInstance: typeof i18n | null = null;

const createI18nInstance = () => {
    const instance = i18n.createInstance();

    instance.use(initReactI18next).init({
        supportedLngs: ['en', 'fr'],
        fallbackLng: 'en',
        interpolation: {
            escapeValue: false,
        },
        partialBundledLanguages: true,
    });

    if (!import.meta.env.SSR) {
        void instance
            .use(LanguageDetector)
            .use(HttpBackend)
            .init({
                detection: {
                    order: ['cookie', 'navigator'],
                    lookupCookie: 'locale',
                    caches: ['cookie'],
                },
            });
    }

    return instance;
};

export const initI18n = (locale: string = 'en', resources = {}) => {
    i18nInstance = createI18nInstance();

    void i18nInstance.init({
        lng: locale,
        resources,
    });

    return i18nInstance;
};

export const setLocale = (locale: string) => {
    if (!i18nInstance) {
        console.error('i18n instance not initialized');
        return;
    }

    if (i18nInstance.languages?.includes(locale)) {
        void i18nInstance.changeLanguage(locale);
    } else {
        console.error(`Locale ${locale} not found.`);
    }
};

export default i18nInstance;
