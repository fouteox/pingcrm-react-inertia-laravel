/**
 * In React this was a `useCallback` hook. Svelte has no re-render of the whole
 * component function, so a plain function is enough: no memoization needed.
 */
export function getInitials(fullName: string): string {
    const names = fullName.trim().split(' ');

    if (names.length === 0) return '';
    if (names.length === 1) return names[0].charAt(0).toUpperCase();

    const firstInitial = names[0].charAt(0);
    const lastInitial = names[names.length - 1].charAt(0);

    return `${firstInitial}${lastInitial}`.toUpperCase();
}
