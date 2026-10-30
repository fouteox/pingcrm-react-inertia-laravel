<script lang="ts">
    import type { HTMLAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    let { ref = $bindable(null), class: className, children, ...restProps }: WithElementRef<HTMLAttributes<HTMLDivElement>, HTMLDivElement> = $props();
</script>

<div
    bind:this={ref}
    role="group"
    data-slot="field"
    data-orientation="vertical"
    class={cn('group/field flex w-full flex-col gap-3 data-[invalid=true]:text-destructive *:w-full [&>.sr-only]:w-auto', className)}
    {...restProps}
>
    {@render children?.()}
</div>
