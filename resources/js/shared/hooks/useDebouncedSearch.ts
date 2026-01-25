import { debounce } from "lodash";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";

interface UseDebouncedSearchOptions {
    delay?: number;
    onDebouncedChange?: (value: string) => void;
}

interface UseDebouncedSearchReturn {
    /** The immediate input value (updates on every keystroke) */
    inputValue: string;
    /** The debounced value (updates after delay) */
    debouncedValue: string;
    /** Update the input value (triggers debounced update) */
    setInputValue: (value: string) => void;
    /** Clear both input and debounced values */
    clear: () => void;
}

/**
 * Hook for handling debounced search input.
 * Maintains both immediate input value and debounced value for API calls.
 *
 * @param options.delay - Debounce delay in milliseconds (default: 500)
 * @param options.onDebouncedChange - Optional callback when debounced value changes
 */
export function useDebouncedSearch(
    options: UseDebouncedSearchOptions = {},
): UseDebouncedSearchReturn {
    const { delay = 500, onDebouncedChange } = options;

    const [inputValue, setInputValueState] = useState("");
    const [debouncedValue, setDebouncedValue] = useState("");

    // Store callback in ref to avoid recreating debounced function
    const onChangeRef = useRef(onDebouncedChange);
    onChangeRef.current = onDebouncedChange;

    // Create debounced function
    const debouncedSetValue = useMemo(
        () =>
            debounce((value: string) => {
                setDebouncedValue(value);
                onChangeRef.current?.(value);
            }, delay),
        [delay],
    );

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            debouncedSetValue.cancel();
        };
    }, [debouncedSetValue]);

    const setInputValue = useCallback(
        (value: string) => {
            setInputValueState(value);
            debouncedSetValue(value);
        },
        [debouncedSetValue],
    );

    const clear = useCallback(() => {
        debouncedSetValue.cancel();
        setInputValueState("");
        setDebouncedValue("");
        onChangeRef.current?.("");
    }, [debouncedSetValue]);

    return {
        inputValue,
        debouncedValue,
        setInputValue,
        clear,
    };
}
