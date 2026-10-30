<script lang="ts">
    import { useEcho } from '@laravel/echo-svelte';

    interface ReverbCompletedEvent {
        type: string;
        status: string;
        message: string;
    }

    let { uuid, onCompleted }: { uuid: string; onCompleted: (message: string) => void } = $props();

    // useEcho subscribes when the component mounts and leaves the channel when it is destroyed.
    // svelte-ignore state_referenced_locally
    useEcho<ReverbCompletedEvent>(`reverb.${uuid}`, '.reverb.completed', (event) => {
        onCompleted(event.message);
    });
</script>
