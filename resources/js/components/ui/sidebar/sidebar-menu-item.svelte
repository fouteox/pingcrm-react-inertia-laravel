<script lang="ts">
    import type { HTMLLiAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, children, ...restProps }: WithElementRef<HTMLLiAttributes, HTMLLIElement> = $props();
</script>

<li bind:this={ref} data-slot="sidebar-menu-item" data-sidebar="menu-item" class={cn('group/menu-item relative', className)} {...restProps}>
    {@render children?.()}
</li>
