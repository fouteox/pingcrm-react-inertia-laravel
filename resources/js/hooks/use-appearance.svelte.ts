/**
 * Appearance (light / dark / system) store.
 *
 * In React this needed `useSyncExternalStore` plus a hand-made listener set.
 * In Svelte 5 a `$state` declared at module level is already a global reactive
 * store: every component that reads `appearance.current` re-renders when it
 * changes. The `.svelte.ts` extension is what allows runes outside components.
 */
type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

const state = $state<{ appearance: Appearance; resolvedAppearance: ResolvedAppearance }>({
    appearance: 'system',
    resolvedAppearance: 'light',
});

let initialized = false;

function readAppearance(value: string | null): Appearance {
    return value === 'light' || value === 'dark' ? value : 'system';
}

function synchronizeAppearance(appearance: Appearance): void {
    const resolvedAppearance = appearance === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : appearance;

    document.documentElement.classList.toggle('dark', resolvedAppearance === 'dark');
    document.documentElement.style.colorScheme = resolvedAppearance;

    state.appearance = appearance;
    state.resolvedAppearance = resolvedAppearance;
}

function updateAppearance(appearance: Appearance): void {
    localStorage.setItem('appearance', appearance);
    document.cookie = `appearance=${appearance};path=/;max-age=31536000;SameSite=Lax`;
    synchronizeAppearance(appearance);
}

export function initializeTheme(): void {
    if (typeof window === 'undefined' || initialized) {
        return;
    }

    initialized = true;
    synchronizeAppearance(readAppearance(localStorage.getItem('appearance')));
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => synchronizeAppearance(state.appearance));
    window.addEventListener('storage', (event) => {
        if (event.key === 'appearance' || event.key === null) {
            synchronizeAppearance(readAppearance(localStorage.getItem('appearance')));
        }
    });
}

/**
 * Getters keep the values reactive: destructuring `state` directly would copy
 * plain values and lose reactivity.
 */
export const appearance = {
    get current() {
        return state.appearance;
    },
    get resolved() {
        return state.resolvedAppearance;
    },
    update: updateAppearance,
};
