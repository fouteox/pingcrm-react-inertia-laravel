<script lang="ts" module>
    import organizations from '@/wayfinder/routes/organizations';

    export const layout = () => ({ breadcrumbs: [{ title: 'Organization', count: 2, href: organizations.index().url }] });
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Trash from '@lucide/svelte/icons/trash';
    import type { Snippet } from 'svelte';
    import AnchorLink from '@/components/anchor-link.svelte';
    import AppHead from '@/components/app-head.svelte';
    import InertiaPagination from '@/components/inertia-pagination.svelte';
    import SearchFilter from '@/components/search-filter.svelte';
    import TableContainer from '@/components/table-container.svelte';
    import { buttonVariants } from '@/components/ui/button';
    import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import type { OrganizationsFilters } from '@/types/filters';
    import type { OrganizationCollection } from '@/types/resources';

    let { organizations: organizationsData }: { filters: OrganizationsFilters; organizations: OrganizationCollection } = $props();

    const { t } = useTranslation();
</script>

<!--
    A snippet is a reusable piece of markup local to this file. React would need
    an extra component (or repeat the JSX); here the three clickable cells share it.
-->
{#snippet linkCell(id: number, name: string, content: Snippet, focusable: boolean)}
    <TableCell class="relative p-2">
        <div class="absolute inset-0 z-10">
            <Link href={organizations.edit(id)} prefetch tabindex={focusable ? undefined : -1} class="block h-full w-full">
                <span class="sr-only">{t('Edit')} {name}</span>
            </Link>
        </div>
        <div class="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
            {@render content()}
        </div>
    </TableCell>
{/snippet}

<AppHead title={t('Organization', { count: 2 })} />

<div class="flex h-full w-full flex-col">
    <div class="mb-6 flex items-center justify-between gap-2">
        <SearchFilter action={organizations.index()} />

        <div class="flex-shrink-0">
            <AnchorLink href={organizations.create().url}>
                <span class="md:hidden">{t('Create')}</span>
                <span class="hidden md:inline">{t('Create Organization')}</span>
            </AnchorLink>
        </div>
    </div>

    <TableContainer>
        <TableHeader>
            <TableRow>
                <TableHead>{t('Name')}</TableHead>
                <TableHead>{t('City')}</TableHead>
                <TableHead>{t('Phone')}</TableHead>
                <TableHead class="w-[50px]"></TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            {#each organizationsData.data as { id, name, city, phone, deleted_at } (id)}
                {#snippet nameContent()}
                    {name}
                    {#if deleted_at}
                        <Trash class="ml-2 size-3 shrink-0 text-muted-foreground" />
                    {/if}
                {/snippet}
                {#snippet cityContent()}{city}{/snippet}
                {#snippet phoneContent()}{phone}{/snippet}
                <TableRow>
                    {@render linkCell(id, name, nameContent, true)}
                    {@render linkCell(id, name, cityContent, false)}
                    {@render linkCell(id, name, phoneContent, false)}
                    <TableCell class="w-px">
                        <Link href={organizations.edit(id)} prefetch tabindex={-1} aria-label="{t('Edit')} {name}" class={buttonVariants({ variant: 'ghost', size: 'icon' })}>
                            <ChevronRight class="size-4 text-muted-foreground" />
                        </Link>
                    </TableCell>
                </TableRow>
            {:else}
                <TableRow>
                    <TableCell colspan={4} class="h-24 text-center">
                        {t('No organizations found.')}
                    </TableCell>
                </TableRow>
            {/each}
        </TableBody>
    </TableContainer>

    <div class="my-6">
        <InertiaPagination links={organizationsData.meta.links} />
    </div>
</div>
