import React from "react";

export const Label = ({ forInput, className, value }) => {
    return (
        <label
            htmlFor={forInput}
            className={`mb-1 block text-gray-700 select-none ` + className}
        >
            {value}
        </label>
    );
};
