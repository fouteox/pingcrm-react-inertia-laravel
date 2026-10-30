<script lang="ts">
    import MoreHorizontalIcon from '@lucide/svelte/icons/ellipsis';
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, ...restProps }: WithElementRef<HTMLAttributes<HTMLSpanElement>, HTMLSpanElement> = $props();
</script>

<span
    bind:this={ref}
    aria-hidden="true"
    data-slot="pagination-ellipsis"
    class={cn("flex size-9 items-center justify-center [&_svg:not([class*='size-'])]:size-4", className)}
    {...restProps}
>
    <MoreHorizontalIcon />
    <span class="sr-only">More pages</span>
</span>
