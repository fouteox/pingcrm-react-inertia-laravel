import { DeletionControls } from '@/components/deletion-controls';
import { ReactElement } from 'react';

export interface UseDeletionControlsOptions {
    resourceId: number;
    isDeleted: boolean;
    resourceType: 'contact' | 'organization' | 'user';
    canDelete?: boolean;
    onDelete: (id: number) => Promise<void>;
    onRestore: (id: number) => Promise<void>;
    processing: boolean;
}

export interface DeletionControlsResult {
    showDeleteControls: () => ReactElement | null;
}

export const useDeletionControls = ({
    resourceId,
    isDeleted,
    resourceType,
    canDelete = true,
    onDelete,
    onRestore,
    processing,
}: UseDeletionControlsOptions): DeletionControlsResult => {
    const handleAction = async (e: React.MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();

        if (!canDelete) {
            return;
        }

        if (isDeleted) {
            await onRestore(resourceId);
        } else {
            await onDelete(resourceId);
        }
    };

    const showDeleteControls = () => {
        if (!canDelete) {
            return null;
        }

        return <DeletionControls resourceType={resourceType} isDeleted={isDeleted} processing={processing} onAction={handleAction} />;
    };

    return {
        showDeleteControls,
    };
};
