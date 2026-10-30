import type { i18n, TOptions } from 'i18next';
import { getContext, setContext } from 'svelte';

/**
 * Svelte replacement for `react-i18next`.
 *
 * `setTranslationContext` is called once in `app.ts` (through the root
 * component context) and every component reads it back with `useTranslation()`,
 * like the React `I18nextProvider` / `useTranslation` pair.
 *
 * Reactivity trick: `t()` reads the `language` and `revision` runes before
 * translating. Any template expression that calls `t(...)` therefore depends on
 * them, and Svelte re-runs it when the language changes or a new bundle loads.
 */
const KEY = Symbol('i18n');

export type Translator = ReturnType<typeof createTranslator>;

function createTranslator(instance: i18n) {
    let language = $state(instance.resolvedLanguage ?? instance.language);
    let revision = $state(0);

    instance.on('languageChanged', (lng) => {
        language = instance.resolvedLanguage ?? lng;
    });
    instance.on('loaded', () => {
        revision++;
    });

    return {
        i18n: instance,
        get language() {
            return language;
        },
        t(key: string, options?: TOptions): string {
            void language;
            void revision;

            return instance.t(key, options) as string;
        },
        changeLanguage(lng: string) {
            return instance.changeLanguage(lng);
        },
    };
}

export function setTranslationContext(instance: i18n, context?: Map<unknown, unknown>): Translator {
    const translator = createTranslator(instance);

    if (context) {
        context.set(KEY, translator);
    } else {
        setContext(KEY, translator);
    }

    return translator;
}

export function useTranslation(): Translator {
    const translator = getContext<Translator | undefined>(KEY);

    if (!translator) {
        throw new Error('useTranslation must be used inside the Inertia app.');
    }

    return translator;
}
