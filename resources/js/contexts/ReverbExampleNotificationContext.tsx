import React, { createContext, useContext, useState } from 'react';

interface ReverbNotificationContextType {
    pendingUuids: Set<string>;
    addUuid: (uuid: string) => void;
    removeUuid: (uuid: string) => void;
}

const ReverbExampleNotificationContext = createContext<ReverbNotificationContextType>({
    pendingUuids: new Set(),
    addUuid: () => {},
    removeUuid: () => {},
});

export function ReverbExampleNotificationProvider({ children }: { children: React.ReactNode }) {
    const [pendingUuids, setPendingUuids] = useState<Set<string>>(new Set());

    const addUuid = (uuid: string) => {
        setPendingUuids((prev) => {
            if (!prev.has(uuid)) {
                return new Set([...prev, uuid]);
            }
            return prev;
        });
    };

    const removeUuid = (uuid: string) => {
        setPendingUuids((prev) => {
            const next = new Set(prev);
            next.delete(uuid);
            return next;
        });
    };

    return <ReverbExampleNotificationContext value={{ pendingUuids, addUuid, removeUuid }}>{children}</ReverbExampleNotificationContext>;
}

export const useReverbNotification = () => useContext(ReverbExampleNotificationContext);
