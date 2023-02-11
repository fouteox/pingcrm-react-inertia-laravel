import React, { forwardRef, useEffect, useRef } from "react";

export default forwardRef(function TextInput(
    {
        type = "text",
        name,
        value,
        className,
        autoComplete,
        required,
        isFocused,
        handleChange,
        maxLength = null,
    },
    ref
) {
    const input = ref ? ref : useRef();

    useEffect(() => {
        if (isFocused) {
            input.current.focus();
        }
    }, []);

    return (
        <input
            type={type}
            name={name}
            id={name}
            value={value}
            maxLength={maxLength}
            className={
                `p-2 leading-normal block w-full border border-gray-200 text-gray-700 bg-white font-sans rounded text-left appearance-none relative focus:ring-indigo-400/50 focus:ring ` +
                className
            }
            ref={input}
            autoComplete={autoComplete}
            required={required}
            onChange={(e) => handleChange(e)}
        />
    );
});
