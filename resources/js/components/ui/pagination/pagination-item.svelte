<script lang="ts">
    import type { HTMLLiAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, children, ...restProps }: WithElementRef<HTMLLiAttributes, HTMLLIElement> = $props();
</script>

<li bind:this={ref} data-slot="pagination-item" class={cn(className)} {...restProps}>
    {@render children?.()}
</li>
