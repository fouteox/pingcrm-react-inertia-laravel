import { afterEach, expect, it, vi } from 'vite-plus/test';
import { initI18n } from './i18n';

afterEach(() => vi.useRealTimers());

it('has bundled translations ready without reinitializing after rendering', async () => {
    vi.useFakeTimers();
    const instance = initI18n('fr', { fr: { translation: { Hello: 'Bonjour' } } });
    const initialized = vi.fn();
    instance.on('initialized', initialized);

    expect(instance.t('Hello')).toBe('Bonjour');
    await vi.runAllTimersAsync();
    expect(initialized).not.toHaveBeenCalled();
});

it('keeps the language and translations isolated between renders', async () => {
    const french = initI18n('fr', { fr: { translation: { Hello: 'Bonjour' } } });
    const english = initI18n('en', { en: { translation: { Hello: 'Hello' } } });

    expect(french.t('Hello')).toBe('Bonjour');
    expect(english.t('Hello')).toBe('Hello');
    await english.changeLanguage('fr');
    expect(french.language).toBe('fr');
    expect(french.t('Hello')).toBe('Bonjour');
});
