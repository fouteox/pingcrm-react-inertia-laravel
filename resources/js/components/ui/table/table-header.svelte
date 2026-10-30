<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, children, ...restProps }: WithElementRef<HTMLAttributes<HTMLTableSectionElement>, HTMLTableSectionElement> = $props();
</script>

<thead bind:this={ref} data-slot="table-header" class={cn('[&_tr]:border-b', className)} {...restProps}>
    {@render children?.()}
</thead>
