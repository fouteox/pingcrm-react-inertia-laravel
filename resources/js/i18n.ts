import { createInstance, type Resource } from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import HttpBackend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

export const initI18n = (locale = 'en', resources: Resource = {}) => {
    const instance = createInstance().use(initReactI18next);

    if (!import.meta.env.SSR) {
        instance.use(LanguageDetector).use(HttpBackend);
    }

    void instance.init({
        lng: locale,
        resources,
        supportedLngs: ['en', 'fr'],
        fallbackLng: 'en',
        interpolation: { escapeValue: false },
        partialBundledLanguages: true,
        detection: {
            order: ['cookie', 'navigator'],
            lookupCookie: 'locale',
            caches: ['cookie'],
        },
    });

    return instance;
};
