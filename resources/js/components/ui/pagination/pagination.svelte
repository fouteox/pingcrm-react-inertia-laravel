<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, children, ...restProps }: WithElementRef<HTMLAttributes<HTMLElement>, HTMLElement> = $props();
</script>

<nav bind:this={ref} data-slot="pagination" aria-label="pagination" class={cn('mx-auto flex w-full justify-center', className)} {...restProps}>
    {@render children?.()}
</nav>
