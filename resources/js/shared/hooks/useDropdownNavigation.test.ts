import { renderHook, act } from '@testing-library/react';
import { useDropdownNavigation } from './useDropdownNavigation';

describe('useDropdownNavigation', () => {
    const defaultOptions = {
        onSelect: vi.fn(),
        onClose: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('returns initial state', () => {
        const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

        expect(result.current.isOpen).toBe(false);
        expect(result.current.highlightedIndex).toBe(-1);
    });

    it('setIsOpen toggles open state', () => {
        const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

        act(() => {
            result.current.setIsOpen(true);
        });
        expect(result.current.isOpen).toBe(true);
    });

    it('reset closes dropdown and calls onClose', () => {
        const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

        act(() => {
            result.current.setIsOpen(true);
        });

        act(() => {
            result.current.reset();
        });

        expect(result.current.isOpen).toBe(false);
        expect(result.current.highlightedIndex).toBe(-1);
        expect(defaultOptions.onClose).toHaveBeenCalled();
    });

    describe('handleKeyDown', () => {
        function createKeyEvent(key: string, value = '') {
            return {
                key,
                preventDefault: vi.fn(),
                currentTarget: { value },
            } as unknown as React.KeyboardEvent<HTMLInputElement>;
        }

        it('ArrowDown increments highlightedIndex', () => {
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.handleKeyDown(createKeyEvent('ArrowDown'), 3);
            });

            expect(result.current.highlightedIndex).toBe(0);
        });

        it('ArrowDown does not exceed item count', () => {
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.setHighlightedIndex(2);
            });
            act(() => {
                result.current.handleKeyDown(createKeyEvent('ArrowDown'), 3);
            });

            expect(result.current.highlightedIndex).toBe(2);
        });

        it('ArrowUp decrements highlightedIndex', () => {
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.setHighlightedIndex(2);
            });
            act(() => {
                result.current.handleKeyDown(createKeyEvent('ArrowUp'), 3);
            });

            expect(result.current.highlightedIndex).toBe(1);
        });

        it('ArrowUp goes to -1 from 0', () => {
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.setHighlightedIndex(0);
            });
            act(() => {
                result.current.handleKeyDown(createKeyEvent('ArrowUp'), 3);
            });

            expect(result.current.highlightedIndex).toBe(-1);
        });

        it('Enter with valid index calls onSelect and resets', () => {
            const onSelect = vi.fn();
            const { result } = renderHook(() =>
                useDropdownNavigation({ ...defaultOptions, onSelect }),
            );

            act(() => {
                result.current.setHighlightedIndex(1);
            });
            act(() => {
                result.current.handleKeyDown(createKeyEvent('Enter'), 3);
            });

            expect(onSelect).toHaveBeenCalledWith(1);
        });

        it('Enter with no selection calls onEnterWithNoSelection callback', () => {
            const onEnterWithNoSelection = vi.fn();
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.handleKeyDown(createKeyEvent('Enter'), 3, {
                    onEnterWithNoSelection,
                });
            });

            expect(onEnterWithNoSelection).toHaveBeenCalled();
        });

        it('Escape resets dropdown', () => {
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.setIsOpen(true);
                result.current.setHighlightedIndex(2);
            });
            act(() => {
                result.current.handleKeyDown(createKeyEvent('Escape'), 3);
            });

            expect(result.current.isOpen).toBe(false);
        });

        it('Backspace on empty input calls onBackspaceEmpty', () => {
            const onBackspaceEmpty = vi.fn();
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.handleKeyDown(createKeyEvent('Backspace', ''), 3, {
                    onBackspaceEmpty,
                });
            });

            expect(onBackspaceEmpty).toHaveBeenCalled();
        });

        it('Backspace on non-empty input does not call onBackspaceEmpty', () => {
            const onBackspaceEmpty = vi.fn();
            const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

            act(() => {
                result.current.handleKeyDown(createKeyEvent('Backspace', 'text'), 3, {
                    onBackspaceEmpty,
                });
            });

            expect(onBackspaceEmpty).not.toHaveBeenCalled();
        });
    });

    it('resets highlightedIndex when dropdown closes', () => {
        const { result } = renderHook(() => useDropdownNavigation(defaultOptions));

        act(() => {
            result.current.setIsOpen(true);
            result.current.setHighlightedIndex(2);
        });

        act(() => {
            result.current.setIsOpen(false);
        });

        expect(result.current.highlightedIndex).toBe(-1);
    });

    describe('click outside behavior', () => {
        it('does not reset when clicking inside the dropdown ref', () => {
            const onClose = vi.fn();
            const { result } = renderHook(() =>
                useDropdownNavigation({ onSelect: vi.fn(), onClose }),
            );

            // Create DOM elements for the refs
            const dropdownEl = document.createElement('div');
            const inputEl = document.createElement('input');
            const childEl = document.createElement('span');
            dropdownEl.appendChild(childEl);
            document.body.appendChild(dropdownEl);
            document.body.appendChild(inputEl);

            // Assign refs
            Object.defineProperty(result.current.dropdownRef, 'current', {
                value: dropdownEl,
                writable: true,
            });
            Object.defineProperty(result.current.inputRef, 'current', {
                value: inputEl,
                writable: true,
            });

            act(() => {
                result.current.setIsOpen(true);
            });

            // Click on the child of dropdown (inside dropdown) - should NOT reset
            act(() => {
                const event = new MouseEvent('mousedown', { bubbles: true });
                Object.defineProperty(event, 'target', { value: childEl });
                document.dispatchEvent(event);
            });

            // The dropdown should still be open because the click was inside
            expect(result.current.isOpen).toBe(true);
            expect(onClose).not.toHaveBeenCalled();

            // Cleanup
            document.body.removeChild(dropdownEl);
            document.body.removeChild(inputEl);
        });

        it('resets when clicking outside both dropdown and input refs', () => {
            const onClose = vi.fn();
            const { result } = renderHook(() =>
                useDropdownNavigation({ onSelect: vi.fn(), onClose }),
            );

            // Create DOM elements for the refs
            const dropdownEl = document.createElement('div');
            const inputEl = document.createElement('input');
            const outsideEl = document.createElement('div');
            document.body.appendChild(dropdownEl);
            document.body.appendChild(inputEl);
            document.body.appendChild(outsideEl);

            // Assign refs
            Object.defineProperty(result.current.dropdownRef, 'current', {
                value: dropdownEl,
                writable: true,
            });
            Object.defineProperty(result.current.inputRef, 'current', {
                value: inputEl,
                writable: true,
            });

            act(() => {
                result.current.setIsOpen(true);
            });

            // Click outside both - should reset
            act(() => {
                outsideEl.dispatchEvent(
                    new MouseEvent('mousedown', { bubbles: true }),
                );
            });

            expect(result.current.isOpen).toBe(false);
            expect(onClose).toHaveBeenCalled();

            // Cleanup
            document.body.removeChild(dropdownEl);
            document.body.removeChild(inputEl);
            document.body.removeChild(outsideEl);
        });
    });

    it('reset works without onClose callback', () => {
        const { result } = renderHook(() =>
            useDropdownNavigation({ onSelect: vi.fn() }),
        );

        act(() => {
            result.current.setIsOpen(true);
        });

        act(() => {
            result.current.reset();
        });

        expect(result.current.isOpen).toBe(false);
    });
});
