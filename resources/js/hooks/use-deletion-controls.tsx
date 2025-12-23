import { DeletionControls } from '@/components/deletion-controls';
import { router } from '@inertiajs/react';
import React, { ReactElement, useState } from 'react';

interface RouteDefinition {
    url: string;
    method: string;
}

export interface UseDeletionControlsOptions {
    isDeleted: boolean;
    resourceType: 'contact' | 'organization' | 'user';
    canDelete?: boolean;
    deleteAction: RouteDefinition;
    restoreAction: RouteDefinition;
}

export interface DeletionControlsResult {
    showDeleteControls: () => ReactElement | null;
}

export const useDeletionControls = ({
    isDeleted,
    resourceType,
    canDelete = true,
    deleteAction,
    restoreAction,
}: UseDeletionControlsOptions): DeletionControlsResult => {
    const [processing, setProcessing] = useState(false);

    const handleAction = async (e: React.MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();

        if (!canDelete || processing) {
            return;
        }

        const action = isDeleted ? restoreAction : deleteAction;

        setProcessing(true);
        router.visit(action.url, {
            method: action.method as 'get' | 'post' | 'put' | 'patch' | 'delete',
            onFinish: () => setProcessing(false),
        });
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
