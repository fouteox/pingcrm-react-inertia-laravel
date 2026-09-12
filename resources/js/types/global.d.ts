import type { SharedData } from './';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedData;
        flashDataType: {
            success?: string;
            error?: string;
        };
    }
}
