<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppLogoIcon from '@/components/app-logo-icon.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { dashboard } from '@/wayfinder/routes';

    let { children, authTitle = '', authDescription = '' }: { children: Snippet; authTitle?: string; authDescription?: string } = $props();

    const { t } = useTranslation();
</script>

<div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
    <div class="w-full max-w-sm">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-4">
                <Link href={dashboard()} class="flex flex-col items-center gap-2 font-medium">
                    <div class="mb-1 flex items-center justify-center rounded-md">
                        <AppLogoIcon class="h-20 w-52 fill-current text-[var(--foreground)] dark:text-white" />
                    </div>
                    <span class="sr-only">{t(authTitle)}</span>
                </Link>

                <div class="space-y-2 text-center">
                    <h1 class="text-xl font-medium">{t(authTitle)}</h1>
                    <p class="text-center text-sm text-muted-foreground">{t(authDescription)}</p>
                </div>
            </div>
            {@render children()}
        </div>
    </div>
</div>
