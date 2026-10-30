<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { buttonVariants, type ButtonSize, type ButtonVariant } from '@/components/ui/button';
    import { cn } from '@/lib/utils';

    let {
        href,
        linkType = 'btn',
        class: className,
        variant = 'default',
        size = 'default',
        children,
    }: {
        href: string;
        linkType?: 'btn' | 'anchor';
        class?: string;
        variant?: ButtonVariant;
        size?: ButtonSize;
        children: Snippet;
    } = $props();
</script>

{#if linkType === 'anchor'}
    <Link {href} class={cn('text-sm text-muted-foreground hover:text-foreground', className)}>
        {@render children()}
    </Link>
{:else}
    <Link {href} class={cn(buttonVariants({ variant, size }), className)}>
        {@render children()}
    </Link>
{/if}
