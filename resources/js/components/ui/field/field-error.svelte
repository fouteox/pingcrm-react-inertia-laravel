<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    /**
     * Simplified from the React version: Inertia gives one message per field,
     * so `message` is a plain string. Nothing is rendered without a message.
     */
    let {
        ref = $bindable(null),
        class: className,
        message,
        ...restProps
    }: WithElementRef<HTMLAttributes<HTMLDivElement>, HTMLDivElement> & { message?: string } = $props();
</script>

{#if message}
    <div bind:this={ref} role="alert" data-slot="field-error" class={cn('text-sm font-normal text-destructive', className)} {...restProps}>
        {message}
    </div>
{/if}
