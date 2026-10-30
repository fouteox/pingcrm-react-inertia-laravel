<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import type { BreadcrumbItem as BreadcrumbItemData } from '@/types';

    let { breadcrumbs }: { breadcrumbs: BreadcrumbItemData[] } = $props();

    const { t } = useTranslation();

    const title = (item: BreadcrumbItemData) => t(item.title, item.count !== undefined ? { count: item.count } : undefined);
</script>

{#if breadcrumbs.length > 0}
    <Breadcrumb>
        <BreadcrumbList>
            {#each breadcrumbs as item, index (index)}
                {@const isLast = index === breadcrumbs.length - 1}
                <BreadcrumbItem>
                    {#if isLast}
                        <BreadcrumbPage>{title(item)}</BreadcrumbPage>
                    {:else}
                        <BreadcrumbLink href={item.href}>
                            {#snippet child({ props })}
                                <Link {...props} href={item.href}>{title(item)}</Link>
                            {/snippet}
                        </BreadcrumbLink>
                    {/if}
                </BreadcrumbItem>
                {#if !isLast}
                    <BreadcrumbSeparator />
                {/if}
            {/each}
        </BreadcrumbList>
    </Breadcrumb>
{/if}
