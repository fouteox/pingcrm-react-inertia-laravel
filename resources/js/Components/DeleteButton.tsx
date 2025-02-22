import React, { MouseEvent, ReactNode } from 'react';

interface DeleteButtonProps {
    onClick: (event: MouseEvent<HTMLButtonElement>) => void;
    children: ReactNode;
}

const DeleteButton: React.FC<DeleteButtonProps> = ({ onClick, children }) => (
    <button
        className="text-red-600 hover:underline focus:outline-hidden"
        tabIndex={-1}
        type="button"
        onClick={onClick}
    >
        {children}
    </button>
);

export default DeleteButton;
