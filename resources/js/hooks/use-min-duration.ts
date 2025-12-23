import { useEffect, useRef, useState } from 'react';

const MIN_DURATION_MS = 800;

/**
 * Ensures a boolean state stays true for at least a minimum duration.
 * Once `isActive` becomes true, the returned value stays true for at least
 * `minDuration` milliseconds, even if `isActive` becomes false earlier.
 */
export function useMinDuration(isActive: boolean, minDuration = MIN_DURATION_MS): boolean {
    const [showActive, setShowActive] = useState(false);
    const startTimeRef = useRef<number | null>(null);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
            timeoutRef.current = null;
        }

        if (isActive) {
            startTimeRef.current = Date.now();
            setShowActive(true);
        } else if (startTimeRef.current !== null) {
            const elapsed = Date.now() - startTimeRef.current;
            const remaining = minDuration - elapsed;

            if (remaining > 0) {
                timeoutRef.current = setTimeout(() => {
                    setShowActive(false);
                    startTimeRef.current = null;
                    timeoutRef.current = null;
                }, remaining);
            } else {
                setShowActive(false);
                startTimeRef.current = null;
            }
        }

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
            }
        };
    }, [isActive, minDuration]);

    return showActive;
}
