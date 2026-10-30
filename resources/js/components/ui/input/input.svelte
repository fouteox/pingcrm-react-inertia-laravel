<script lang="ts">
    import type { HTMLInputAttributes } from 'svelte/elements';
    import { cn, type WithElementRef } from '@/lib/utils';

    /**
     * `value` is `$bindable`: a parent can write `<Input bind:value={form.email} />`
     * and get two-way binding, instead of the React `value` + `onChange` pair.
     */
    let { ref = $bindable(null), value = $bindable(), class: className, ...restProps }: WithElementRef<HTMLInputAttributes, HTMLInputElement> = $props();
</script>

<input
    bind:this={ref}
    bind:value
    data-slot="input"
    class={cn(
        'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40',
        className,
    )}
    {...restProps}
/>
