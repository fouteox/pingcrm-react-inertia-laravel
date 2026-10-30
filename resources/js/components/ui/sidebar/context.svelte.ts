import { getContext, setContext } from 'svelte';
import { IsMobile } from '@/hooks/use-mobile.svelte';

export const SIDEBAR_COOKIE_NAME = 'sidebar_state';
export const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 7;
export const SIDEBAR_WIDTH = '16rem';
export const SIDEBAR_WIDTH_MOBILE = '18rem';
export const SIDEBAR_WIDTH_ICON = '3rem';
const SIDEBAR_KEYBOARD_SHORTCUT = 'b';

type Getter<T> = () => T;

type SidebarStateProps = {
    open: Getter<boolean>;
    setOpen: (open: boolean) => void;
};

/**
 * The React version kept this in `useState` + `useCallback` + `useMemo` so the
 * context value stayed stable. A class with `$state` fields does the same job:
 * consumers read `sidebar.open` and re-render only when it changes.
 */
class SidebarState {
    readonly props: SidebarStateProps;
    open = $derived.by(() => this.props.open());
    openMobile = $state(false);
    setOpen: SidebarStateProps['setOpen'];
    #isMobile = new IsMobile();
    state = $derived.by(() => (this.open ? 'expanded' : 'collapsed'));

    constructor(props: SidebarStateProps) {
        this.setOpen = props.setOpen;
        this.props = props;
    }

    get isMobile() {
        return this.#isMobile.current;
    }

    handleShortcutKeydown = (event: KeyboardEvent) => {
        if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            this.toggle();
        }
    };

    setOpenMobile = (value: boolean) => {
        this.openMobile = value;
    };

    toggle = () => {
        if (this.#isMobile.current) {
            this.openMobile = !this.openMobile;
        } else {
            this.setOpen(!this.open);
        }
    };
}

const SYMBOL_KEY = 'scn-sidebar';

export function setSidebar(props: SidebarStateProps): SidebarState {
    return setContext(Symbol.for(SYMBOL_KEY), new SidebarState(props));
}

export function useSidebar(): SidebarState {
    return getContext(Symbol.for(SYMBOL_KEY));
}
