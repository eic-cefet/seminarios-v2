import { useCallback, useEffect, useRef, useState } from "react";

interface UseDropdownNavigationOptions {
    onSelect: (index: number) => void;
    onClose?: () => void;
}

interface KeyboardHandlerCallbacks {
    onEnterWithNoSelection?: () => void;
    onBackspaceEmpty?: () => void;
}

interface UseDropdownNavigationReturn {
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
    highlightedIndex: number;
    setHighlightedIndex: (index: number) => void;
    inputRef: React.RefObject<HTMLInputElement | null>;
    dropdownRef: React.RefObject<HTMLDivElement | null>;
    handleKeyDown: (
        e: React.KeyboardEvent<HTMLInputElement>,
        itemCount: number,
        callbacks?: KeyboardHandlerCallbacks,
    ) => void;
    reset: () => void;
    focusInput: () => void;
}

/**
 * Hook for managing dropdown navigation with keyboard support.
 * Handles arrow key navigation, enter to select, escape to close,
 * and click-outside detection.
 *
 * @example
 * const {
 *   isOpen,
 *   setIsOpen,
 *   highlightedIndex,
 *   inputRef,
 *   dropdownRef,
 *   handleKeyDown,
 *   reset,
 *   focusInput,
 * } = useDropdownNavigation({
 *   onSelect: (index) => addItem(suggestions[index]),
 *   onClose: () => clearSearch(),
 * });
 */
export function useDropdownNavigation({
    onSelect,
    onClose,
}: UseDropdownNavigationOptions): UseDropdownNavigationReturn {
    const [isOpen, setIsOpen] = useState(false);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const inputRef = useRef<HTMLInputElement>(null);
    const dropdownRef = useRef<HTMLDivElement>(null);

    const reset = useCallback(() => {
        setIsOpen(false);
        setHighlightedIndex(-1);
        onClose?.();
    }, [onClose]);

    const focusInput = useCallback(() => {
        inputRef.current?.focus();
    }, []);

    const handleKeyDown = useCallback(
        (
            e: React.KeyboardEvent<HTMLInputElement>,
            itemCount: number,
            callbacks?: KeyboardHandlerCallbacks,
        ) => {
            switch (e.key) {
                case "Enter":
                    e.preventDefault();
                    if (highlightedIndex >= 0 && highlightedIndex < itemCount) {
                        onSelect(highlightedIndex);
                        reset();
                        focusInput();
                    } else {
                        callbacks?.onEnterWithNoSelection?.();
                    }
                    break;

                case "Escape":
                    reset();
                    break;

                case "ArrowDown":
                    e.preventDefault();
                    setHighlightedIndex((prev) =>
                        prev < itemCount - 1 ? prev + 1 : prev,
                    );
                    break;

                case "ArrowUp":
                    e.preventDefault();
                    setHighlightedIndex((prev) => (prev > 0 ? prev - 1 : -1));
                    break;

                case "Backspace":
                    if (
                        e.currentTarget.value === "" &&
                        callbacks?.onBackspaceEmpty
                    ) {
                        callbacks.onBackspaceEmpty();
                    }
                    break;
            }
        },
        [highlightedIndex, onSelect, reset, focusInput],
    );

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            const target = event.target as Node;
            const isOutsideDropdown =
                dropdownRef.current && !dropdownRef.current.contains(target);
            const isOutsideInput =
                inputRef.current && !inputRef.current.contains(target);

            if (isOutsideDropdown && isOutsideInput) {
                reset();
            }
        };

        if (isOpen) {
            document.addEventListener("mousedown", handleClickOutside);
            return () =>
                document.removeEventListener("mousedown", handleClickOutside);
        }
    }, [isOpen, reset]);

    // Reset highlighted index when dropdown closes
    useEffect(() => {
        if (!isOpen) {
            setHighlightedIndex(-1);
        }
    }, [isOpen]);

    return {
        isOpen,
        setIsOpen,
        highlightedIndex,
        setHighlightedIndex,
        inputRef,
        dropdownRef,
        handleKeyDown,
        reset,
        focusInput,
    };
}
