import React from "react";
import { Error } from "./Error";
import { Label } from "./Label";

export const Field = ({ label, value, errors = null, children }) => {
    return (
        <div className="w-full pb-7 pr-6 lg:w-1/2">
            <Label forInput={label} value={value} />
            {children}
            <Error message={errors} />
        </div>
    );
};
