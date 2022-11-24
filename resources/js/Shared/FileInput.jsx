import { useState, useRef } from "react";
import { filesize } from "@/utils";

const Button = ({ text, onClick }) => (
    <button
        type="button"
        className="px-4 py-1 text-xs font-medium text-white bg-gray-500 rounded-sm focus:outline-none hover:bg-gray-700"
        onClick={onClick}
    >
        {text}
    </button>
);

export default ({ className, name, label, accept, errors = [], onChange }) => {
    const fileInput = useRef();
    const [file, setFile] = useState(null);

    function browse() {
        fileInput.current.click();
    }

    function remove() {
        setFile(null);
        onChange(null);
        fileInput.current.value = null;
    }

    function handleFileChange(e) {
        const file = e.target.files[0];
        setFile(file);
        onChange(file);
    }

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
                className={`p-2 leading-normal block w-full border border-gray-200 text-gray-700 bg-white font-sans rounded text-left appearance-none relative focus:ring-indigo-400/50 focus:ring ${
                    errors.length && "error"
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
                {!file && <Button text="Browse" onClick={browse} />}
                {file && (
                    <div className="flex items-center justify-between p-2">
                        <div className="flex-1 pr-1">
                            {file.name}
                            <span className="ml-1 text-xs text-gray-600">
                                ({filesize(file.size)})
                            </span>
                        </div>
                        <Button text="Remove" onClick={remove} />
                    </div>
                )}
            </div>
            {errors.length > 0 && <div className="form-error">{errors}</div>}
        </div>
    );
};
