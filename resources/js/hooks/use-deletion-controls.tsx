import DeleteButton from '@/components/DeleteButton';
import Modal from '@/components/Modal';
import TrashedMessage from '@/components/TrashedMessage';
import React, { ReactElement, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface UseDeletionControlsOptions {
    resourceId: number;
    isDeleted: boolean;
    resourceType: 'contact' | 'organization' | 'user';
    canDelete?: boolean;
    onDelete: (id: number) => Promise<void>;
    onRestore: (id: number) => Promise<void>;
    processing: boolean;
}

interface DeletionControls {
    showDeleteControls: () => ReactElement | null;
    isModalOpen: boolean;
    closeModal: () => void;
}

export const useDeletionControls = ({
    resourceId,
    isDeleted,
    resourceType,
    canDelete = true,
    onDelete,
    onRestore,
    processing,
}: UseDeletionControlsOptions): DeletionControls => {
    const { t } = useTranslation();
    const [showModal, setShowModal] = useState(false);

    const closeModal = () => setShowModal(false);

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

        closeModal();
    };

    const getResourceString = () => {
        const capitalize = (str: string) =>
            str.charAt(0).toUpperCase() + str.slice(1);
        return t(capitalize(resourceType));
    };

    const showDeleteControls = () => {
        // Si on ne peut pas supprimer, on ne montre pas les contrôles
        if (!canDelete) {
            return null;
        }

        return (
            <>
                {isDeleted ? (
                    <TrashedMessage onRestore={() => setShowModal(true)}>
                        {t(`This ${resourceType} has been deleted.`)}
                    </TrashedMessage>
                ) : (
                    <DeleteButton onClick={() => setShowModal(true)}>
                        {t(`Delete ${getResourceString()}`)}
                    </DeleteButton>
                )}
                <Modal
                    show={showModal}
                    onClose={closeModal}
                    onConfirm={handleAction}
                    title={
                        isDeleted
                            ? t(
                                  `Are you sure you want to restore this ${resourceType}?`,
                              )
                            : t(
                                  `Are you sure you want to delete this ${resourceType}?`,
                              )
                    }
                    confirmText={
                        isDeleted
                            ? t(`Restore ${getResourceString()}`)
                            : t(`Delete ${getResourceString()}`)
                    }
                    cancelText={t('Cancel')}
                    isProcessing={processing}
                />
            </>
        );
    };

    return {
        showDeleteControls,
        isModalOpen: showModal,
        closeModal,
    };
};
