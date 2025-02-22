import i18n from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import HttpBackend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

void i18n
    .use(LanguageDetector)
    .use(HttpBackend)
    .use(initReactI18next)
    .init({
        supportedLngs: ['en', 'fr'],
        fallbackLng: 'en',
    });

export default i18n;
