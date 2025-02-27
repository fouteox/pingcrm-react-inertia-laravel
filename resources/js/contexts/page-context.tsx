import { BreadcrumbItem } from '@/types';
import React, { createContext, useContext, useReducer } from 'react';

interface PageState {
    authTitle: string;
    authDescription: string;
    breadcrumbs: BreadcrumbItem[];
}

type PageAction = { type: 'SET_AUTH_INFO'; title: string; description: string } | { type: 'SET_BREADCRUMBS'; items: BreadcrumbItem[] };

interface PageContextType extends PageState {
    dispatch: React.Dispatch<PageAction>;
}

const initialState: PageState = {
    authTitle: '',
    authDescription: '',
    breadcrumbs: [],
};

function pageReducer(state: PageState, action: PageAction): PageState {
    switch (action.type) {
        case 'SET_AUTH_INFO':
            if (state.authTitle === action.title && state.authDescription === action.description) {
                return state;
            }
            return {
                ...state,
                authTitle: action.title,
                authDescription: action.description,
            };
        case 'SET_BREADCRUMBS':
            return {
                ...state,
                breadcrumbs: action.items,
            };
        default:
            return state;
    }
}

const PageContext = createContext<PageContextType>({
    ...initialState,
    dispatch: () => {},
});

export const usePageContext = () => useContext(PageContext);

export const usePageActions = () => {
    const { dispatch } = usePageContext();

    return React.useMemo(
        () => ({
            setAuthInfo: (title: string, description: string) => {
                dispatch({ type: 'SET_AUTH_INFO', title, description });
            },
            setBreadcrumbs: (items: BreadcrumbItem[]) => {
                dispatch({ type: 'SET_BREADCRUMBS', items });
            },
        }),
        [dispatch],
    );
};

export const LayoutProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [state, dispatch] = useReducer(pageReducer, initialState);

    const contextValue = React.useMemo(
        () => ({
            ...state,
            dispatch,
        }),
        [state],
    );

    return <PageContext value={contextValue}>{children}</PageContext>;
};
