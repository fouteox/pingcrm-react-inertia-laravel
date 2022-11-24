import React from 'react';

export default function InputLabel({ forInput, value, className, children }) {
    return (
        <label htmlFor={forInput} className={`mb-1 block text-gray-700 select-none ` + className}>
            {value ? value : children}
        </label>
    );
}
