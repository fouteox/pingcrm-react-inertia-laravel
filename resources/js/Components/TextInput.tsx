import clsx from 'clsx';
import React, {
    ChangeEvent,
    ForwardedRef,
    forwardRef,
    useEffect,
    useRef,
} from 'react';

interface TextInputProps {
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
    name: string;
    value: string | number;
    className?: string;
    autoComplete?: string;
    required?: boolean;
    isFocused?: boolean;
    handleChange: (e: ChangeEvent<HTMLInputElement>) => void;
    maxLength?: number | null;
}

const TextInput = forwardRef<HTMLInputElement, TextInputProps>(
    (
        {
            type = 'text',
            name,
            value,
            className = '',
            autoComplete,
            required = false,
            isFocused = false,
            handleChange,
            maxLength = null,
        }: TextInputProps,
        ref: ForwardedRef<HTMLInputElement>,
    ) => {
        const localRef = useRef<HTMLInputElement>(null);
        const inputRef = (ref ?? localRef) as React.RefObject<HTMLInputElement>;

        useEffect(() => {
            if (isFocused && inputRef.current) {
                inputRef.current.focus();
            }
        }, [inputRef, isFocused]);

        return (
            <input
                type={type}
                name={name}
                id={name}
                value={value}
                maxLength={maxLength ?? undefined}
                className={clsx(
                    'relative block w-full appearance-none rounded-sm border border-gray-200',
                    'bg-white p-2 text-left font-sans leading-normal text-gray-700',
                    'focus:ring-3 focus:ring-indigo-400/50',
                    className,
                )}
                ref={ref ?? localRef}
                autoComplete={autoComplete}
                required={required}
                onChange={handleChange}
            />
        );
    },
);

TextInput.displayName = 'TextInput';

export default TextInput;
