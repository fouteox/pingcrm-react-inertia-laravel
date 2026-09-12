import { useSyncExternalStore } from 'react';

type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

type AppearanceSnapshot = {
    appearance: Appearance;
    resolvedAppearance: ResolvedAppearance;
};

const serverSnapshot: AppearanceSnapshot = { appearance: 'system', resolvedAppearance: 'light' };
let snapshot = serverSnapshot;
const listeners = new Set<() => void>();
let initialized = false;

function readAppearance(value: string | null): Appearance {
    return value === 'light' || value === 'dark' ? value : 'system';
}

function synchronizeAppearance(appearance: Appearance): void {
    const resolvedAppearance = appearance === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : appearance;

    document.documentElement.classList.toggle('dark', resolvedAppearance === 'dark');
    document.documentElement.style.colorScheme = resolvedAppearance;

    if (snapshot.appearance !== appearance || snapshot.resolvedAppearance !== resolvedAppearance) {
        snapshot = { appearance, resolvedAppearance };
        listeners.forEach((listener) => listener());
    }
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
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => synchronizeAppearance(snapshot.appearance));
    window.addEventListener('storage', (event) => {
        if (event.key === 'appearance' || event.key === null) {
            synchronizeAppearance(readAppearance(localStorage.getItem('appearance')));
        }
    });
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);
    return () => {
        listeners.delete(listener);
    };
}

export function useAppearance() {
    const current = useSyncExternalStore(
        subscribe,
        () => snapshot,
        () => serverSnapshot,
    );
    return { ...current, updateAppearance };
}
