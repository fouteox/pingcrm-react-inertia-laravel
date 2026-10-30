<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { buttonVariants } from '@/components/ui/button';
    import { Pagination, PaginationContent, PaginationEllipsis, PaginationItem } from '@/components/ui/pagination';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { cn } from '@/lib/utils';

    type InertiaLinkType = { url: string | null; label: string; active: boolean };

    let { links, class: className }: { links: InertiaLinkType[]; class?: string } = $props();

    const { t } = useTranslation();

    // First link is "Previous", last link is "Next", page links are in between.
    const prevLink = $derived(links[0]);
    const nextLink = $derived(links[links.length - 1]);
    const pageLinks = $derived(links.slice(1, -1));

    // Determine which page links to show on mobile
    const activeIndex = $derived(pageLinks.findIndex((link) => link.active));
    const totalPages = $derived(pageLinks.length);

    const entities: Record<string, string> = { laquo: '«', raquo: '»', amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ' };

    const cleanLabel = (label: string) =>
        label
            .replace(/&#(\d+);/g, (_, code: string) => String.fromCharCode(Number.parseInt(code, 10)))
            .replace(/&#x([0-9a-f]+);/gi, (_, code: string) => String.fromCharCode(Number.parseInt(code, 16)))
            .replace(/&(laquo|raquo|amp|lt|gt|quot|apos|nbsp);/g, (_, name: string) => entities[name] ?? '');

    const getMobileVisibility = (index: number) => {
        if (totalPages <= 5) return true; // Show all if 5 or fewer

        const mobileStart = Math.max(0, Math.min(activeIndex - 1, totalPages - 3));
        return index >= mobileStart && index < mobileStart + 3;
    };

    const navClass = cn(buttonVariants({ variant: 'ghost', size: 'default' }), 'gap-1 px-2.5');
    const disabledNavClass = cn(buttonVariants({ variant: 'ghost', size: 'default' }), 'pointer-events-none gap-1 px-2.5 opacity-50');
</script>

<!-- Hide pagination if there's only one page -->
{#if links.length > 3}
    <Pagination class={className}>
        <PaginationContent>
            <!-- Previous button -->
            <PaginationItem>
                {#if prevLink.url}
                    <Link href={prevLink.url} class={navClass} aria-label={t('Go to previous page')}>
                        <ChevronLeft class="size-4" />
                        <span class="hidden sm:inline">{t('Previous')}</span>
                    </Link>
                {:else}
                    <span class={disabledNavClass} aria-disabled="true">
                        <ChevronLeft class="size-4" />
                        <span class="hidden sm:inline">{t('Previous')}</span>
                    </span>
                {/if}
            </PaginationItem>

            <!-- Page links -->
            {#each pageLinks as link, i (i)}
                <PaginationItem class={!getMobileVisibility(i) ? 'hidden sm:block' : undefined}>
                    {#if link.url}
                        <Link
                            href={link.url}
                            class={buttonVariants({ variant: link.active ? 'outline' : 'ghost', size: 'icon' })}
                            aria-current={link.active ? 'page' : undefined}
                            aria-label="{t('Page')} {cleanLabel(link.label)}"
                        >
                            {cleanLabel(link.label)}
                        </Link>
                    {:else if link.label.includes('...')}
                        <PaginationEllipsis />
                    {:else}
                        <span
                            class={cn(buttonVariants({ variant: link.active ? 'outline' : 'ghost', size: 'icon' }), 'pointer-events-none')}
                            aria-disabled="true"
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {cleanLabel(link.label)}
                        </span>
                    {/if}
                </PaginationItem>
            {/each}

            <!-- Next button -->
            <PaginationItem>
                {#if nextLink.url}
                    <Link href={nextLink.url} class={navClass} aria-label={t('Go to next page')}>
                        <span class="hidden sm:inline">{t('Next')}</span>
                        <ChevronRight class="size-4" />
                    </Link>
                {:else}
                    <span class={disabledNavClass} aria-disabled="true">
                        <span class="hidden sm:inline">{t('Next')}</span>
                        <ChevronRight class="size-4" />
                    </span>
                {/if}
            </PaginationItem>
        </PaginationContent>
    </Pagination>
{/if}
