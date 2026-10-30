<script lang="ts">
    import type { ComponentProps } from 'svelte';
    import { Input } from '@/components/ui/input';
    import { cn } from '@/lib/utils';

    let { ref = $bindable(null), value = $bindable(), class: className, ...restProps }: ComponentProps<typeof Input> = $props();
</script>

<Input
    bind:ref
    bind:value
    data-slot="input-group-control"
    class={cn('flex-1 rounded-none border-0 bg-transparent shadow-none ring-0 focus-visible:ring-0 aria-invalid:ring-0 dark:bg-transparent', className)}
    {...restProps}
/>
