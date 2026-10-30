<script lang="ts">
    import type { HTMLTableAttributes } from 'svelte/elements';
    import { ScrollArea } from '@/components/ui/scroll-area';
    import { cn } from '@/lib/utils';

    let { class: className, children, ...props }: HTMLTableAttributes = $props();
</script>

<ScrollArea class="overflow-contain grid w-full grid-cols-1" orientation="both">
    <table data-slot="table" class={cn('w-full caption-bottom text-sm', className)} {...props}>
        {@render children?.()}
    </table>
</ScrollArea>
