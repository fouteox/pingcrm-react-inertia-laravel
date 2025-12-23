import React, { createContext, useCallback, useContext, useState } from 'react';

interface ProcessingContextType {
    isProcessing: boolean;
    setProcessing: (value: boolean) => void;
}

const ProcessingContext = createContext<ProcessingContextType>({
    isProcessing: false,
    setProcessing: () => {},
});

export const useProcessingContext = () => useContext(ProcessingContext);

export const ProcessingProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [isProcessing, setIsProcessing] = useState(false);

    const setProcessing = useCallback((value: boolean) => {
        setIsProcessing(value);
    }, []);

    const contextValue = React.useMemo(
        () => ({
            isProcessing,
            setProcessing,
        }),
        [isProcessing, setProcessing],
    );

    return <ProcessingContext value={contextValue}>{children}</ProcessingContext>;
};
