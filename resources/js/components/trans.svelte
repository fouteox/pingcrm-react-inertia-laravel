<script lang="ts">
    import type { Snippet } from 'svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    /**
     * Svelte version of react-i18next's `<Trans>`.
     *
     * A translation such as "Built with <react_link>React</react_link>" is split
     * into text and tag segments. Each tag is rendered by the snippet of the same
     * name declared inside `<Trans>`: snippets declared as children of a component
     * are passed to it as props, so `{#snippet react_link(text)}` becomes
     * `props.react_link`.
     */
    let { i18nKey, ...snippets }: { i18nKey: string; [tag: string]: Snippet<[string]> | string } = $props();

    const { t } = useTranslation();

    type Segment = { tag: string | null; text: string };

    const segments = $derived.by(() => {
        const result: Segment[] = [];
        const pattern = /<(\w+)>(.*?)<\/\1>/g;
        const translation = t(i18nKey);
        let lastIndex = 0;

        for (const match of translation.matchAll(pattern)) {
            result.push({ tag: null, text: translation.slice(lastIndex, match.index) });
            result.push({ tag: match[1], text: match[2] });
            lastIndex = match.index + match[0].length;
        }

        result.push({ tag: null, text: translation.slice(lastIndex) });

        return result;
    });
</script>

{#each segments as segment, index (index)}
    {@const render = segment.tag ? snippets[segment.tag] : undefined}
    {#if typeof render === 'function'}{@render render(segment.text)}{:else}{segment.text}{/if}
{/each}
