import { fileSize } from '@/utils';
import React, { ComponentProps, useRef, useState } from 'react';

interface FileInputProps extends Omit<ComponentProps<'input'>, 'onChange'> {
    error?: string | string[];
    onChange?: (file: File | null) => void;
    label?: string;
    name: string;
    className?: string;
}

interface ButtonProps {
    text: string;
    onClick: () => void;
}

const Button: React.FC<ButtonProps> = ({ text, onClick }) => (
    <button
        type="button"
        className="rounded-xs bg-gray-500 px-4 py-1 text-xs font-medium text-white hover:bg-gray-700 focus:outline-hidden"
        onClick={onClick}
    >
        {text}
    </button>
);

const FileInput: React.FC<FileInputProps> = ({
    className,
    name,
    label,
    accept,
    error,
    onChange = () => {},
}) => {
    const fileInput = useRef<HTMLInputElement>(null);
    const [file, setFile] = useState<File | null>(null);

    const handleBrowse = () => {
        fileInput.current?.click();
    };

    const remove = () => {
        setFile(null);
        onChange(null);
        if (fileInput.current) {
            fileInput.current.value = '';
        }
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0] || null;
        setFile(selectedFile);
        onChange(selectedFile);
    };

    const errors = Array.isArray(error) ? error : error ? [error] : [];

    return (
        <div className={className}>
            {label && (
                <label
                    className="mb-1 block text-gray-700 select-none"
                    htmlFor={name}
                >
                    {label}:
                </label>
            )}
            <div
                className={`relative block w-full appearance-none rounded border border-gray-200 bg-white p-2 text-left font-sans leading-normal text-gray-700 focus:ring-3 focus:ring-indigo-400/50 ${
                    errors.length ? 'error' : ''
                }`}
            >
                <input
                    id={name}
                    ref={fileInput}
                    accept={accept}
                    type="file"
                    className="hidden"
                    onChange={handleFileChange}
                />
                {!file && <Button text="Browse" onClick={handleBrowse} />}
                {file && (
                    <div className="flex items-center justify-between p-2">
                        <div className="flex-1 pr-1">
                            {file.name}
                            <span className="ml-1 text-xs text-gray-600">
                                ({fileSize(file.size)})
                            </span>
                        </div>
                        <Button text="Remove" onClick={remove} />
                    </div>
                )}
            </div>
            {errors.length > 0 && (
                <div className="form-error">
                    {errors.map((err, index) => (
                        <div key={index}>{err}</div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default FileInput;
