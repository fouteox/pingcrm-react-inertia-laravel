import { usePage } from "@inertiajs/react";
import { useEffect } from "react";
import toast, { Toaster } from "react-hot-toast";

export default () => {
    const { flash, errors } = usePage().props;
    const numOfErrors = Object.keys(errors).length;

    useEffect(() => {
        flash.success && toast.success(flash.success);

        if (flash.error || numOfErrors > 0) {
            toast.error(
                numOfErrors === 1
                    ? "There is one form error"
                    : `There are ${numOfErrors} form errors.`
            );
        }
    }, [flash, errors]);

    return <Toaster />;
};
