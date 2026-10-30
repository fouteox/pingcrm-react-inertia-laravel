<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, children, ...restProps }: WithElementRef<HTMLAttributes<HTMLElement>, HTMLElement> = $props();
</script>

<nav bind:this={ref} data-slot="breadcrumb" aria-label="breadcrumb" class={cn(className)} {...restProps}>
    {@render children?.()}
</nav>
