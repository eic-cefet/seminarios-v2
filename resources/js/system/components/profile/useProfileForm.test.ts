import { describe, it, expect, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useProfileForm } from './useProfileForm';

vi.mock('@shared/lib/errors', () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
    getFieldErrors: vi.fn((err: Error) => {
        if (err.message === 'validation-error') {
            return { email: 'Invalid email' };
        }
        return {};
    }),
}));

describe('useProfileForm', () => {
    it('returns isEditing as false initially', () => {
        const { result } = renderHook(() => useProfileForm());

        expect(result.current.isEditing).toBe(false);
    });

    it('startEditing sets isEditing to true', () => {
        const { result } = renderHook(() => useProfileForm());

        act(() => {
            result.current.startEditing();
        });

        expect(result.current.isEditing).toBe(true);
    });

    it('handleCancel resets state', () => {
        const onCancel = vi.fn();
        const { result } = renderHook(() => useProfileForm({ onCancel }));

        // Set up some state first
        act(() => {
            result.current.startEditing();
            result.current.mutationCallbacks.onError(new Error('test error'));
        });

        expect(result.current.isEditing).toBe(true);
        expect(result.current.error).toBe('test error');

        // Cancel
        act(() => {
            result.current.handleCancel();
        });

        expect(result.current.isEditing).toBe(false);
        expect(result.current.error).toBe(null);
        expect(result.current.fieldErrors).toEqual({});
        expect(onCancel).toHaveBeenCalled();
    });

    it('mutationCallbacks.onSuccess sets success=true and isEditing=false', async () => {
        const onSuccess = vi.fn();
        const { result } = renderHook(() => useProfileForm({ onSuccess }));

        act(() => {
            result.current.startEditing();
        });

        await act(async () => {
            await result.current.mutationCallbacks.onSuccess();
        });

        expect(result.current.success).toBe(true);
        expect(result.current.isEditing).toBe(false);
        expect(result.current.error).toBe(null);
        expect(result.current.fieldErrors).toEqual({});
        expect(onSuccess).toHaveBeenCalled();
    });

    it('mutationCallbacks.onSuccess resets success to false after 3 seconds', async () => {
        vi.useFakeTimers();
        const onSuccess = vi.fn();
        const { result } = renderHook(() => useProfileForm({ onSuccess }));

        act(() => {
            result.current.startEditing();
        });

        await act(async () => {
            await result.current.mutationCallbacks.onSuccess();
        });

        expect(result.current.success).toBe(true);

        // Advance timer by 3 seconds
        act(() => {
            vi.advanceTimersByTime(3000);
        });

        expect(result.current.success).toBe(false);
        vi.useRealTimers();
    });

    it('mutationCallbacks.onError sets error message', () => {
        const { result } = renderHook(() => useProfileForm());

        act(() => {
            result.current.mutationCallbacks.onError(new Error('Something went wrong'));
        });

        expect(result.current.error).toBe('Something went wrong');
    });

    it('mutationCallbacks.onError sets field errors', () => {
        const { result } = renderHook(() => useProfileForm());

        act(() => {
            result.current.mutationCallbacks.onError(new Error('validation-error'));
        });

        expect(result.current.fieldErrors).toEqual({ email: 'Invalid email' });
    });
});
