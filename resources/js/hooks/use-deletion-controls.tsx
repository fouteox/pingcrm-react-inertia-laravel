import { DeletionControls } from '@/components/deletion-controls';
import React, { ReactElement } from 'react';

export interface UseDeletionControlsOptions {
    isDeleted: boolean;
    resourceType: 'contact' | 'organization' | 'user';
    canDelete?: boolean;
    onDelete: () => Promise<void>;
    onRestore: () => Promise<void>;
    processing: boolean;
}

export interface DeletionControlsResult {
    showDeleteControls: () => ReactElement | null;
}

export const useDeletionControls = ({
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
            await onRestore();
        } else {
            await onDelete();
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
