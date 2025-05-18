import { useReverbNotification } from '@/contexts/reverb-context';
import { useEcho } from '@laravel/echo-react';
import { toast } from 'sonner';

interface ReverbCompletedEvent {
    type: string;
    status: string;
    message: string;
}

export function ReverbNotificationListener() {
    const { pendingUuids, removeUuid } = useReverbNotification();

    return (
        <>
            {Array.from(pendingUuids).map((uuid) => (
                <ReverbChannelListener
                    key={uuid}
                    uuid={uuid}
                    onCompleted={(message) => {
                        toast.success(message);
                        removeUuid(uuid);
                    }}
                />
            ))}
        </>
    );
}

interface ReverbChannelListenerProps {
    uuid: string;
    onCompleted: (message: string) => void;
}

function ReverbChannelListener({ uuid, onCompleted }: ReverbChannelListenerProps) {
    useEcho<ReverbCompletedEvent>(`reverb.${uuid}`, '.reverb.completed', (e) => {
        onCompleted(e.message);
    });

    return null;
}
