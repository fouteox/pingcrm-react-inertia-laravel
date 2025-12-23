import React, { createContext, useCallback, useContext, useState } from 'react';

interface ProcessingContextType {
    isProcessing: boolean;
    setProcessing: (value: boolean) => void;
    showSuccess: boolean;
    setShowSuccess: (value: boolean) => void;
}

const ProcessingContext = createContext<ProcessingContextType>({
    isProcessing: false,
    setProcessing: () => {},
    showSuccess: false,
    setShowSuccess: () => {},
});

export const useProcessingContext = () => useContext(ProcessingContext);

export const ProcessingProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [isProcessing, setIsProcessing] = useState(false);
    const [showSuccess, setShowSuccessState] = useState(false);

    const setProcessing = useCallback((value: boolean) => {
        setIsProcessing(value);
    }, []);

    const setShowSuccess = useCallback((value: boolean) => {
        setShowSuccessState(value);
    }, []);

    const contextValue = React.useMemo(
        () => ({
            isProcessing,
            setProcessing,
            showSuccess,
            setShowSuccess,
        }),
        [isProcessing, setProcessing, showSuccess, setShowSuccess],
    );

    return <ProcessingContext value={contextValue}>{children}</ProcessingContext>;
};
