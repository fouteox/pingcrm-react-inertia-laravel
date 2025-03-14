import { useReverbNotification } from '@/contexts/reverb-context';
import { Broadcaster } from 'laravel-echo';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

interface ReverbCompletedEvent {
    type: string;
    status: string;
    message: string;
}

type ReverbChannel = Broadcaster['reverb']['private'];

interface ChannelSubscription {
    uuid: string;
    channel: ReverbChannel;
    active: boolean;
}

export function ReverbNotificationListener() {
    const { pendingUuids, removeUuid } = useReverbNotification();
    const activeSubscriptions = useRef<ChannelSubscription[]>([]);

    useEffect(() => {
        if (!window.Echo) return;

        Array.from(pendingUuids).forEach((uuid) => {
            if (!activeSubscriptions.current.find((sub) => sub.uuid === uuid)) {
                const channel = window.Echo.private(`reverb.${uuid}`);

                channel.listen('.reverb.completed', (e: ReverbCompletedEvent) => {
                    toast.success(e.message);
                    removeUuid(uuid);

                    const subscription = activeSubscriptions.current.find((sub) => sub.uuid === uuid);
                    if (subscription) {
                        subscription.active = false;
                    }
                });

                activeSubscriptions.current.push({
                    uuid,
                    channel,
                    active: true,
                });
            }
        });

        return () => {
            activeSubscriptions.current = activeSubscriptions.current.filter((sub) => {
                if (!pendingUuids.has(sub.uuid) || !sub.active) {
                    sub.channel.stopListening('.reverb.completed');
                    sub.channel.unsubscribe();
                    return false;
                }
                return true;
            });
        };
    }, [pendingUuids, removeUuid]);

    return null;
}
