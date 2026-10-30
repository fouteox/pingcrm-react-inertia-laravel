<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Filter from '@lucide/svelte/icons/filter';
    import X from '@lucide/svelte/icons/x';
    import { Button } from '@/components/ui/button';
    import { ButtonGroup } from '@/components/ui/button-group';
    import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput } from '@/components/ui/input-group';
    import { Label } from '@/components/ui/label';
    import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { useTranslation } from '@/hooks/use-translation.svelte';
    import type { RouteDefinition } from '@/wayfinder';

    interface Values {
        role: string;
        search: string;
        trashed: string;
    }

    type Filters = { role?: string; search?: string; trashed?: string };

    const ANY_VALUE = 'any';

    function pickBy(object: Values): Partial<Values> {
        const keys: Array<keyof Values> = ['role', 'search', 'trashed'];
        return keys.reduce<Partial<Values>>((acc, key) => {
            const value = object[key];
            if (value !== '' && (key === 'search' || value !== ANY_VALUE)) {
                acc[key] = value;
            }
            return acc;
        }, {});
    }

    let { action, showRole = false }: { action: RouteDefinition<'get'>; showRole?: boolean } = $props();

    const { t } = useTranslation();
    const filters = (page.props.filters ?? {}) as Filters;
    let open = $state(false);

    // A `$state` object: assigning `values.search = ...` is enough to update the UI.
    let values = $state<Values>({
        role: filters.role || ANY_VALUE,
        search: filters.search || '',
        trashed: filters.trashed || ANY_VALUE,
    });

    const roleItems = $derived([
        { value: ANY_VALUE, label: t('Any') },
        { value: 'user', label: t('User') },
        { value: 'owner', label: t('Owner') },
    ]);

    const trashedItems = $derived([
        { value: ANY_VALUE, label: t('Any') },
        { value: 'with', label: t('With Trashed') },
        { value: 'only', label: t('Only Trashed') },
    ]);

    function handleChange(key: keyof Values, value: string) {
        values[key] = value;

        router.get(action.url, pickBy(values), {
            replace: true,
            preserveState: true,
        });
    }
</script>

<ButtonGroup class="w-64">
    <Popover bind:open>
        <PopoverTrigger>
            {#snippet child({ props })}
                <Button {...props} variant="outline" size="icon" aria-label={t('Filter')}>
                    <Filter />
                </Button>
            {/snippet}
        </PopoverTrigger>
        <PopoverContent class="w-auto min-w-72" align="start">
            <div class="grid gap-4">
                {#if showRole}
                    <div class="grid gap-2">
                        <Label for="role">{t('Role')}</Label>
                        <Select type="single" items={roleItems} value={values.role} onValueChange={(value) => handleChange('role', value)}>
                            <SelectTrigger id="role" class="w-full">
                                {roleItems.find((item) => item.value === values.role)?.label ?? t('Select role')}
                            </SelectTrigger>
                            <SelectContent>
                                {#each roleItems as { value, label } (value)}
                                    <SelectItem {value} {label} />
                                {/each}
                            </SelectContent>
                        </Select>
                    </div>
                {/if}
                <div class="grid gap-2">
                    <Label for="trashed">{t('Trashed')}</Label>
                    <Select type="single" items={trashedItems} value={values.trashed} onValueChange={(value) => handleChange('trashed', value)}>
                        <SelectTrigger id="trashed" class="w-full">
                            {trashedItems.find((item) => item.value === values.trashed)?.label ?? t('Select trashed status')}
                        </SelectTrigger>
                        <SelectContent>
                            {#each trashedItems as { value, label } (value)}
                                <SelectItem {value} {label} />
                            {/each}
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </PopoverContent>
    </Popover>
    <InputGroup>
        <InputGroupInput
            type="text"
            placeholder={t('Search')}
            aria-label={t('Search')}
            value={values.search}
            oninput={(event) => handleChange('search', event.currentTarget.value)}
        />
        {#if values.search}
            <InputGroupAddon align="inline-end">
                <InputGroupButton size="icon-sm" aria-label={t('Clear')} onclick={() => handleChange('search', '')}>
                    <X />
                </InputGroupButton>
            </InputGroupAddon>
        {/if}
    </InputGroup>
</ButtonGroup>
