import {router, usePage} from "@inertiajs/react";
import {useCallback, useEffect, useRef} from "react";
import toast, { Toaster } from "react-hot-toast";

export default () => {
    const { flash, errors } = usePage().props;
    const numOfErrors = Object.keys(errors).length;

    const currentNavId = useRef(null)
    const lastShownNavId = useRef(null)

    const handleNavigationStart = useCallback(() => {
        currentNavId.current = Date.now().toString()
    }, [])

    useEffect(() => {
        const removeStartEventListener = router.on('start', handleNavigationStart)

        return () => {
            removeStartEventListener()
        }
    }, [handleNavigationStart])

    useEffect(() => {
        if (currentNavId.current !== lastShownNavId.current) {
            flash.success && toast.success(flash.success);

            if (flash.error || numOfErrors > 0) {
                toast.error(
                    numOfErrors === 1
                        ? "There is one form error"
                        : `There are ${numOfErrors} form errors.`
                );
            }

            lastShownNavId.current = currentNavId.current
        }
    }, [flash, errors]);

    return <Toaster />;
};
