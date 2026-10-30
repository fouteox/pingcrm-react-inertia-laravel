<script lang="ts">
    import { Field, FieldError, FieldLabel } from '@/components/ui/field';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';

    let {
        id,
        label,
        error,
        value = $bindable(''),
        items,
        disabled,
    }: {
        id: string;
        label: string;
        error?: string;
        value: string;
        items: { value: string; label: string }[];
        disabled?: boolean;
    } = $props();

    // `$derived` recomputes only when `value` or `items` change (React would recompute on every render).
    const selectedLabel = $derived(items.find((item) => item.value === value)?.label ?? '');
</script>

<Field data-invalid={!!error || undefined}>
    <FieldLabel for={id}>{label}</FieldLabel>
    <Select type="single" name={id} {items} bind:value {disabled}>
        <SelectTrigger {id} class="w-full" aria-invalid={!!error || undefined} aria-describedby={error ? `${id}-error` : undefined}>
            {selectedLabel}
        </SelectTrigger>
        <SelectContent>
            {#each items as item (item.value)}
                <SelectItem value={item.value} label={item.label} />
            {/each}
        </SelectContent>
    </Select>
    <FieldError id="{id}-error" message={error} />
</Field>
