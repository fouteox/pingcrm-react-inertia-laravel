<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import Trash from '@lucide/svelte/icons/trash';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import {
        AlertDialog,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogTitle,
        AlertDialogTrigger,
    } from '@/components/ui/alert-dialog';
    import { Button } from '@/components/ui/button';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import type { RouteDefinition } from '@/wayfinder';

    let {
        resourceType,
        isDeleted,
        canDelete = true,
        deleteAction,
        restoreAction,
    }: {
        resourceType: 'contact' | 'organization' | 'user';
        isDeleted: boolean;
        canDelete?: boolean;
        deleteAction: RouteDefinition<'delete'>;
        restoreAction: RouteDefinition<'put'>;
    } = $props();

    const { t } = useTranslation();
    const form = useForm({});
    let open = $state(false);

    const resourceName = $derived(resourceType.charAt(0).toUpperCase() + resourceType.slice(1));

    function onAction() {
        form.submit(isDeleted ? restoreAction : deleteAction, {
            preserveScroll: true,
            onSuccess: () => (open = false),
        });
    }
</script>

{#if canDelete}
    <AlertDialog bind:open>
        {#if isDeleted}
            <Alert
                class="mb-6 max-w-3xl items-center border-yellow-500 bg-yellow-100 text-yellow-800 dark:border-yellow-600/30 dark:bg-yellow-600/10 dark:text-yellow-500"
            >
                <Trash class="text-yellow-800 dark:text-yellow-500" />
                <AlertDescription class="flex w-full items-center justify-between text-yellow-700 dark:text-yellow-500/90">
                    {t(`This ${resourceType} has been deleted.`)}
                    <!-- `child` snippet: bits-ui gives the trigger props, we render our own Button with them. -->
                    <AlertDialogTrigger>
                        {#snippet child({ props })}
                            <Button
                                {...props}
                                variant="ghost"
                                size="sm"
                                class="-mr-2 text-yellow-800 hover:bg-yellow-200/50 dark:text-yellow-500 dark:hover:bg-yellow-800/20"
                            >
                                {t('Restore')}
                            </Button>
                        {/snippet}
                    </AlertDialogTrigger>
                </AlertDescription>
            </Alert>
        {:else}
            <AlertDialogTrigger>
                {#snippet child({ props })}
                    <Button {...props} type="button" variant="destructive">{t(`Delete ${resourceName}`)}</Button>
                {/snippet}
            </AlertDialogTrigger>
        {/if}

        <AlertDialogContent>
            <AlertDialogTitle>
                {isDeleted ? t(`Are you sure you want to restore this ${resourceType}?`) : t(`Are you sure you want to delete this ${resourceType}?`)}
            </AlertDialogTitle>
            <AlertDialogDescription>
                {isDeleted
                    ? t(`This will restore the ${resourceType} and make it visible again.`)
                    : t(`The ${resourceType} will be moved to trash, but can be restored later.`)}
            </AlertDialogDescription>

            <AlertDialogFooter class="gap-2">
                <AlertDialogCancel variant="secondary">{t('Cancel')}</AlertDialogCancel>
                <Button type="button" variant={isDeleted ? 'default' : 'destructive'} disabled={form.processing} onclick={onAction}>
                    {isDeleted ? t(`Restore ${resourceName}`) : t(`Delete ${resourceName}`)}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
{/if}
