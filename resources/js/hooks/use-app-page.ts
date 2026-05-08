import type { PageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/react';

export function useAppPage<T extends PageProps = PageProps>() {
    return usePage<T>();
}
