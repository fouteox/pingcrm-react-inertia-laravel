<script lang="ts" module>
    import organizations from '@/wayfinder/routes/organizations';
    import type { OrganizationResource } from '@/types/resources';

    type EditPageProps = { organization: OrganizationResource };

    export const layout = ({ organization }: EditPageProps) => ({
        breadcrumbs: [
            { title: 'Organization', count: 2, href: organizations.index().url },
            { title: organization.name, href: organizations.edit(organization.id).url },
        ],
    });
</script>

<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Trash from '@lucide/svelte/icons/trash';
    import type { Snippet } from 'svelte';
    import AppHead from '@/components/app-head.svelte';
    import DeletionControls from '@/components/deletion-controls.svelte';
    import OrganizationFormFields, { type OrganizationFormData } from '@/components/organization-form-fields.svelte';
    import SubmitButton from '@/components/submit-button.svelte';
    import TableContainer from '@/components/table-container.svelte';
    import { buttonVariants } from '@/components/ui/button';
    import { FieldGroup } from '@/components/ui/field';
    import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/OrganizationsController';
    import contacts from '@/wayfinder/routes/contacts';

    let { organization }: EditPageProps = $props();

    const { t } = useTranslation();

    // svelte-ignore state_referenced_locally
    let form = $state(
        useForm<OrganizationFormData>({
            name: organization.name || '',
            email: organization.email || '',
            phone: organization.phone || '',
            address: organization.address || '',
            city: organization.city || '',
            region: organization.region || '',
            country: organization.country || '',
            postal_code: organization.postal_code || '',
        }),
    );

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();
        form.submit(update(organization), {
            preserveScroll: true,
        });
    }
</script>

{#snippet linkCell(id: number, name: string, content: Snippet, focusable: boolean)}
    <TableCell class="relative p-2">
        <div class="absolute inset-0 z-10">
            <Link href={contacts.edit(id)} prefetch tabindex={focusable ? undefined : -1} class="block h-full w-full">
                <span class="sr-only">{t('Edit')} {name}</span>
            </Link>
        </div>
        <div class="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
            {@render content()}
        </div>
    </TableCell>
{/snippet}

<AppHead title={form.name} />

{#if organization.deleted_at}
    <DeletionControls
        isDeleted={!!organization.deleted_at}
        resourceType="organization"
        deleteAction={destroy(organization)}
        restoreAction={restore(organization)}
    />
{/if}

<div class="max-w-3xl">
    <h2 class="mb-6 text-xl font-semibold">{t('Edit Organization')}</h2>

    <form {onsubmit}>
        <FieldGroup>
            <OrganizationFormFields bind:form />

            <div class="flex flex-col justify-end gap-4 sm:flex-row">
                {#if !organization.deleted_at}
                    <DeletionControls
                        isDeleted={!!organization.deleted_at}
                        resourceType="organization"
                        deleteAction={destroy(organization)}
                        restoreAction={restore(organization)}
                    />
                {/if}

                <SubmitButton processing={form.processing} recentlySuccessful={form.recentlySuccessful}>
                    {t('Update Organization')}
                </SubmitButton>
            </div>
        </FieldGroup>
    </form>
</div>

<h2 class="mt-12 mb-6 text-lg font-semibold">{t('Contact', { count: 2 })}</h2>

<TableContainer>
    <TableHeader>
        <TableRow>
            <TableHead>{t('Name')}</TableHead>
            <TableHead>{t('City')}</TableHead>
            <TableHead colspan={2}>{t('Phone')}</TableHead>
        </TableRow>
    </TableHeader>
    <TableBody>
        {#each organization.contacts as { id, name, phone, city, deleted_at } (id)}
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
                    <Link href={contacts.edit(id)} prefetch tabindex={-1} aria-label="{t('Edit')} {name}" class={buttonVariants({ variant: 'ghost', size: 'icon' })}>
                        <ChevronRight class="size-4 text-muted-foreground" />
                    </Link>
                </TableCell>
            </TableRow>
        {:else}
            <TableRow>
                <TableCell colspan={4} class="h-24 text-center">
                    {t('No contacts found.')}
                </TableCell>
            </TableRow>
        {/each}
    </TableBody>
</TableContainer>
