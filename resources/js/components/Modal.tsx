import DangerButton from '@/components/DangerButton';
import SecondaryButton from '@/components/SecondaryButton';
import {
    Dialog,
    DialogBackdrop,
    DialogPanel,
    DialogTitle,
} from '@headlessui/react';
import React from 'react';

interface ModalProps {
    show: boolean;
    onClose: () => void;
    onConfirm: (e: React.MouseEvent<HTMLButtonElement>) => void;
    title: string;
    confirmText: string;
    cancelText: string;
    isProcessing?: boolean;
}

const Modal: React.FC<ModalProps> = ({
    show,
    onClose,
    onConfirm,
    title,
    confirmText,
    cancelText,
    isProcessing = false,
}) => {
    return (
        <Dialog open={show} onClose={onClose} className="relative z-50">
            <DialogBackdrop
                transition
                className="fixed inset-0 bg-gray-500/75 duration-300 ease-out data-[closed]:opacity-0"
            />

            <div className="fixed inset-0 flex w-screen items-center justify-center p-4">
                <DialogPanel
                    transition
                    className="w-full max-w-lg transform overflow-hidden rounded-lg bg-white shadow-xl duration-300 ease-out data-[closed]:scale-95 data-[closed]:opacity-0"
                >
                    <div className="p-6">
                        <DialogTitle className="text-lg font-medium text-gray-900">
                            {title}
                        </DialogTitle>

                        <div className="mt-6 flex justify-end">
                            <SecondaryButton onClick={onClose}>
                                {cancelText}
                            </SecondaryButton>

                            <DangerButton
                                className="ml-3"
                                processing={isProcessing}
                                type="button"
                                onClick={onConfirm}
                            >
                                {confirmText}
                            </DangerButton>
                        </div>
                    </div>
                </DialogPanel>
            </div>
        </Dialog>
    );
};

export default Modal;
