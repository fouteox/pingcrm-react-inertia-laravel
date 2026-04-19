import type { Page } from '@inertiajs/core';
import { usePage } from '@inertiajs/react';
import type { Inertia } from '@/wayfinder/types';

export function useAppPage<T = Record<string, never>>() {
    return usePage() as unknown as Page<T & Inertia.SharedData>;
}
