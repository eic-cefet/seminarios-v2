import { renderHook, act } from '@testing-library/react';
import { useDebouncedSearch } from './useDebouncedSearch';

describe('useDebouncedSearch', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('returns initial empty values', () => {
        const { result } = renderHook(() => useDebouncedSearch());
        expect(result.current.inputValue).toBe('');
        expect(result.current.debouncedValue).toBe('');
    });

    it('updates inputValue immediately', () => {
        const { result } = renderHook(() => useDebouncedSearch());

        act(() => {
            result.current.setInputValue('hello');
        });

        expect(result.current.inputValue).toBe('hello');
        expect(result.current.debouncedValue).toBe('');
    });

    it('updates debouncedValue after delay', () => {
        const { result } = renderHook(() => useDebouncedSearch({ delay: 300 }));

        act(() => {
            result.current.setInputValue('test');
        });

        expect(result.current.debouncedValue).toBe('');

        act(() => {
            vi.advanceTimersByTime(300);
        });

        expect(result.current.debouncedValue).toBe('test');
    });

    it('uses default 500ms delay', () => {
        const { result } = renderHook(() => useDebouncedSearch());

        act(() => {
            result.current.setInputValue('test');
        });

        act(() => {
            vi.advanceTimersByTime(400);
        });
        expect(result.current.debouncedValue).toBe('');

        act(() => {
            vi.advanceTimersByTime(100);
        });
        expect(result.current.debouncedValue).toBe('test');
    });

    it('calls onDebouncedChange callback', () => {
        const onDebouncedChange = vi.fn();
        const { result } = renderHook(() =>
            useDebouncedSearch({ delay: 100, onDebouncedChange }),
        );

        act(() => {
            result.current.setInputValue('search');
        });

        act(() => {
            vi.advanceTimersByTime(100);
        });

        expect(onDebouncedChange).toHaveBeenCalledWith('search');
    });

    it('clear resets both values', () => {
        const onDebouncedChange = vi.fn();
        const { result } = renderHook(() =>
            useDebouncedSearch({ delay: 100, onDebouncedChange }),
        );

        act(() => {
            result.current.setInputValue('test');
        });
        act(() => {
            vi.advanceTimersByTime(100);
        });

        act(() => {
            result.current.clear();
        });

        expect(result.current.inputValue).toBe('');
        expect(result.current.debouncedValue).toBe('');
        expect(onDebouncedChange).toHaveBeenCalledWith('');
    });

    it('cancels pending debounce on clear', () => {
        const { result } = renderHook(() => useDebouncedSearch({ delay: 300 }));

        act(() => {
            result.current.setInputValue('test');
        });

        act(() => {
            result.current.clear();
        });

        act(() => {
            vi.advanceTimersByTime(300);
        });

        expect(result.current.debouncedValue).toBe('');
    });
});
