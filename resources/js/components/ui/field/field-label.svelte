<script lang="ts">
    import type { ComponentProps } from 'svelte';
    import { Label } from '@/components/ui/label';
    import { cn } from '@/lib/utils';

    let { ref = $bindable(null), class: className, ...restProps }: ComponentProps<typeof Label> = $props();
</script>

<Label
    bind:ref
    data-slot="field-label"
    class={cn(
        'group/field-label peer/field-label flex w-fit gap-2 leading-snug group-data-[disabled=true]/field:opacity-50 has-data-checked:border-primary/30 has-data-checked:bg-primary/5 has-[>[data-slot=field]]:rounded-md has-[>[data-slot=field]]:border *:data-[slot=field]:p-3 dark:has-data-checked:border-primary/20 dark:has-data-checked:bg-primary/10',
        'has-[>[data-slot=field]]:w-full has-[>[data-slot=field]]:flex-col',
        className,
    )}
    {...restProps}
/>
