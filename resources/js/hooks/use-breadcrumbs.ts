import { useEffect } from 'react';
import { usePageActions } from '@/contexts/page-context';
import { BreadcrumbItem } from '@/types';

export function useBreadcrumbs(items: BreadcrumbItem[]): void {
    const { setBreadcrumbs } = usePageActions();
    const key = JSON.stringify(items);

    useEffect(() => {
        setBreadcrumbs(items);
        // Using `key` as dependency to detect content changes without relying on
        // a referentially stable `items` array at each call site.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key, setBreadcrumbs]);
}
