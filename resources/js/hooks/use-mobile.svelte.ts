import { MediaQuery } from 'svelte/reactivity';

const MOBILE_BREAKPOINT = 768;

/**
 * `MediaQuery` from `svelte/reactivity` replaces the React `useSyncExternalStore`
 * hook: `isMobile.current` is reactive and falls back to `false` during SSR.
 */
export class IsMobile extends MediaQuery {
    constructor(breakpoint: number = MOBILE_BREAKPOINT) {
        super(`max-width: ${breakpoint - 1}px`, false);
    }
}
