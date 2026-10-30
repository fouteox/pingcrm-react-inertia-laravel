<script lang="ts">
    import CheckIcon from '@lucide/svelte/icons/check';
    import { Checkbox as CheckboxPrimitive } from 'bits-ui';
    import { cn, type WithoutChildrenOrChild } from '@/lib/utils';

    let { ref = $bindable(null), checked = $bindable(false), class: className, ...restProps }: WithoutChildrenOrChild<CheckboxPrimitive.RootProps> = $props();
</script>

<CheckboxPrimitive.Root
    bind:ref
    bind:checked
    data-slot="checkbox"
    class={cn(
        'peer relative flex size-4 shrink-0 items-center justify-center rounded-[4px] border border-input shadow-xs transition-shadow outline-none group-has-disabled/field:opacity-50 after:absolute after:-inset-x-3 after:-inset-y-2 focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:bg-input/30 data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
        className,
    )}
    {...restProps}
>
    <!-- bits-ui passes the current state to the `children` snippet. -->
    {#snippet children({ checked })}
        <div data-slot="checkbox-indicator" class="grid place-content-center text-current transition-none [&>svg]:size-3.5">
            {#if checked}
                <CheckIcon />
            {/if}
        </div>
    {/snippet}
</CheckboxPrimitive.Root>
