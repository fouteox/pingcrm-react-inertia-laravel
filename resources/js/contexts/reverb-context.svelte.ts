import { getContext, setContext } from 'svelte';
import { SvelteSet } from 'svelte/reactivity';

/**
 * React needed `useState(new Set())` and copied the set on every change so the
 * reference changed. `SvelteSet` is a reactive Set: `add`/`delete` are enough.
 */
const KEY = Symbol('reverb-notifications');

type ReverbNotificationContext = {
    pendingUuids: SvelteSet<string>;
    addUuid: (uuid: string) => void;
    removeUuid: (uuid: string) => void;
};

export function provideReverbNotifications(): ReverbNotificationContext {
    const pendingUuids = new SvelteSet<string>();

    return setContext(KEY, {
        pendingUuids,
        addUuid: (uuid: string) => pendingUuids.add(uuid),
        removeUuid: (uuid: string) => pendingUuids.delete(uuid),
    });
}

export function useReverbNotification(): ReverbNotificationContext {
    return getContext<ReverbNotificationContext>(KEY);
}
