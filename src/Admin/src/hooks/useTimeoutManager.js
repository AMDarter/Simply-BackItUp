import { useRef, useEffect } from 'react';

const useTimeoutManager = () => {
    const timeoutsRef = useRef(new Map());

    // Set a new timeout
    const set = (callback, delay) => {
        const timeoutId = setTimeout(callback, delay);
        timeoutsRef.current.set(timeoutId, timeoutId);
        return timeoutId;
    };

    // Clear a specific timeout
    const clear = (timeoutId) => {
        clearTimeout(timeoutId);
        timeoutsRef.current.delete(timeoutId);
    };

    // Clear all timeouts
    const clearAll = () => {
        timeoutsRef.current.forEach((timeoutId) => clearTimeout(timeoutId));
        timeoutsRef.current.clear();
    };

    // Cleanup on component unmount
    useEffect(() => {
        return () => clearAll();
    }, []);

    return { set, clear, clearAll };
};

export default useTimeoutManager;
