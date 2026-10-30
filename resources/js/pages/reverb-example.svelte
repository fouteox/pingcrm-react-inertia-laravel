<script lang="ts" module>
    import reverb from '@/wayfinder/routes/reverb';

    export const layout = () => ({ breadcrumbs: [{ title: 'Reverb Demo', href: reverb.index().url }] });
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { toast } from 'svelte-sonner';
    import { v4 as uuidv4 } from 'uuid';
    import AppHead from '@/components/app-head.svelte';
    import { Button } from '@/components/ui/button';
    import { useReverbNotification } from '@/contexts/reverb-context.svelte';
    import { useTranslation } from '@/hooks/use-translation.svelte';

    const { t } = useTranslation();
    const { addUuid } = useReverbNotification();
    const form = useForm({ uuid: '' });

    function onsubmit(event: SubmitEvent) {
        event.preventDefault();
        const uuid = uuidv4();

        form.transform(() => ({ uuid }));

        form.submit(reverb.store(), {
            preserveScroll: true,
            onSuccess: () => addUuid(uuid),
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error);
                }
            },
        });
    }
</script>

<AppHead title={t('Reverb Demo')} />

<h1 class="mb-4 text-xl font-bold">{t('Reverb Demo')}</h1>

<p class="mb-4">{t('This page demonstrates the use of Reverb for real-time notifications. When you click the button below:')}</p>
<ul class="mb-4 list-inside list-disc space-y-2">
    <li>{t('A background job will be triggered')}</li>
    <li>{t('You can navigate to other pages')}</li>
    <li>{t('A notification will appear after about 5 seconds')}</li>
</ul>

<form {onsubmit}>
    <Button type="submit" disabled={form.processing}>
        {form.processing ? t('Processing') : t('Send')}
    </Button>
</form>
