<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Loader2 from '@lucide/svelte/icons/loader-circle';
    import { Button, type ButtonProps } from '@/components/ui/button';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    let {
        processing,
        recentlySuccessful = false,
        disabled,
        children,
        ...props
    }: Omit<ButtonProps, 'type'> & { processing: boolean; recentlySuccessful?: boolean } = $props();

    const { t } = useTranslation();
</script>

<div class="flex items-center gap-3">
    <span role="status" class="text-sm text-muted-foreground">
        {#if recentlySuccessful && !processing}{t('Saved.')}{/if}
    </span>
    <Button type="submit" disabled={disabled || processing} {...props}>
        {#if processing}
            <Loader2 class="size-4 animate-spin" />
        {:else if recentlySuccessful}
            <Check class="size-4" />
        {/if}
        {@render children?.()}
    </Button>
</div>
