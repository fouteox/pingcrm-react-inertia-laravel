import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Helper types used by the shadcn-svelte style components in `components/ui`.
 * They mirror `React.ComponentProps<'div'>` + `ref` from the React version:
 * Svelte has no `forwardRef`, a parent gets the DOM node through `bind:ref`.
 */
export type WithoutChild<T> = T extends { child?: unknown } ? Omit<T, 'child'> : T;
type WithoutChildren<T> = T extends { children?: unknown } ? Omit<T, 'children'> : T;
export type WithoutChildrenOrChild<T> = WithoutChildren<WithoutChild<T>>;
export type WithElementRef<T, U extends HTMLElement = HTMLElement> = T & { ref?: U | null };
