<script lang="ts">
    import { toast } from 'svelte-sonner';
    import ReverbChannelListener from '@/components/reverb-channel-listener.svelte';
    import { useReverbNotification } from '@/contexts/reverb-context.svelte';

    const { pendingUuids, removeUuid } = useReverbNotification();
</script>

<!-- One listener component per pending job: a keyed each block mounts / unmounts them as the set changes. -->
{#each [...pendingUuids] as uuid (uuid)}
    <ReverbChannelListener
        {uuid}
        onCompleted={(message) => {
            toast.success(message);
            removeUuid(uuid);
        }}
    />
{/each}
