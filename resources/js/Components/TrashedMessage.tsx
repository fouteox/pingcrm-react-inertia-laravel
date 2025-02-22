import Icon from '@/Components/Icon';
import React, { MouseEvent, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface TrashedMessageProps {
    onRestore: (event: MouseEvent<HTMLButtonElement>) => void;
    children: ReactNode;
}

const TrashedMessage: React.FC<TrashedMessageProps> = ({
    onRestore,
    children,
}) => {
    const { t } = useTranslation();

    return (
        <div className="mb-6 flex max-w-3xl items-center justify-between rounded-sm border border-yellow-500 bg-yellow-400 p-4">
            <div className="flex items-center">
                <Icon
                    name="trash"
                    className="mr-2 h-4 w-4 shrink-0 fill-current text-yellow-800"
                />
                <div className="text-yellow-800">{children}</div>
            </div>
            <button
                className="text-sm text-yellow-800 hover:underline focus:outline-hidden"
                tabIndex={-1}
                type="button"
                onClick={onRestore}
            >
                {t('Restore')}
            </button>
        </div>
    );
};

export default TrashedMessage;
