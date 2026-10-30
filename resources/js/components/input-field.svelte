<script lang="ts">
    import type { ComponentProps } from 'svelte';
    import { Field, FieldError, FieldLabel } from '@/components/ui/field';
    import { Input } from '@/components/ui/input';

    /**
     * `value` is bindable so forms can write `<InputField bind:value={form.email} />`:
     * the binding goes InputField -> Input -> <input>, no onChange handler needed.
     */
    let {
        id,
        label,
        error,
        value = $bindable(),
        ...props
    }: ComponentProps<typeof Input> & { id: string; label: string; error?: string } = $props();
</script>

<Field data-invalid={!!error || undefined}>
    <FieldLabel for={id}>{label}</FieldLabel>
    <Input {...props} bind:value {id} aria-invalid={!!error || undefined} aria-describedby={error ? `${id}-error` : undefined} />
    <FieldError id="{id}-error" message={error} />
</Field>
