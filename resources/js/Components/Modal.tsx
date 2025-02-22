import { Dialog, DialogBackdrop, DialogPanel } from '@headlessui/react';
import React, { ReactNode } from 'react';

type MaxWidthType = 'sm' | 'md' | 'lg' | 'xl' | '2xl';

interface ModalProps {
    children: ReactNode;
    show?: boolean;
    maxWidth?: MaxWidthType;
    closeable?: boolean;
    onClose: () => void;
}

const Modal: React.FC<ModalProps> = ({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose,
}) => {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth];

    return (
        <Dialog open={show} onClose={close} className="relative z-50">
            <DialogBackdrop
                transition
                className="fixed inset-0 bg-gray-500/75 duration-300 ease-out data-[closed]:opacity-0"
            />

            <div className="fixed inset-0 flex w-screen items-center justify-center p-4">
                <DialogPanel
                    transition
                    className={`w-full transform overflow-hidden rounded-lg bg-white shadow-xl duration-300 ease-out data-[closed]:scale-95 data-[closed]:opacity-0 ${maxWidthClass}`}
                >
                    {children}
                </DialogPanel>
            </div>
        </Dialog>
    );
};

export default Modal;
