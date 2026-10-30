<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { toast } from 'svelte-sonner';
    import { Toaster } from '@/components/ui/sonner';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    const { t } = useTranslation();

    // `onMount` only runs in the browser; returning a function cleans up on destroy
    // (the React version used two `useEffect`s returning the unsubscribe function).
    onMount(() => {
        const removeFlashListener = router.on('flash', ({ detail: { flash } }) => {
            if (flash.success) {
                toast.success(flash.success);
            }
            if (flash.error) {
                toast.error(flash.error);
            }
        });

        const removeErrorListener = router.on('error', ({ detail: { errors } }) => {
            toast.error(t('form_errors', { count: Object.keys(errors).length }));
        });

        return () => {
            removeFlashListener();
            removeErrorListener();
        };
    });
</script>

<Toaster />
