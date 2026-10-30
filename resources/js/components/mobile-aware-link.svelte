<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { ComponentProps } from 'svelte';
    import { useSidebar } from '@/components/ui/sidebar';

    let { children, onclick, ...props }: Omit<ComponentProps<typeof Link>, 'onclick'> & { onclick?: (event: MouseEvent) => void } = $props();

    const sidebar = useSidebar();

    function handleClick(event: MouseEvent) {
        if (sidebar.isMobile) {
            sidebar.setOpenMobile(false);
        }

        onclick?.(event);
    }
</script>

<!-- Inertia's Link types `onclick` without arguments, but forwards the DOM event at runtime. -->
<Link {...props} onclick={handleClick as () => void}>
    {@render children?.()}
</Link>
