import { getErrorMessage, getFieldErrors, API_ERROR_MESSAGES } from './errors';
import { ApiRequestError } from '@shared/api/client';

describe('getErrorMessage', () => {
    it('returns mapped message for known API error code', () => {
        const error = new ApiRequestError('unauthenticated', 'Unauthenticated', 401);
        expect(getErrorMessage(error)).toBe(API_ERROR_MESSAGES.unauthenticated);
    });

    it('returns error.message for unknown API error code', () => {
        const error = new ApiRequestError('custom_code', 'Custom message', 400);
        expect(getErrorMessage(error)).toBe('Custom message');
    });

    it('returns unknown_error fallback when no code or message', () => {
        const error = new ApiRequestError('', '', 500);
        expect(getErrorMessage(error)).toBe(API_ERROR_MESSAGES.unknown_error);
    });

    it('returns message from plain Error', () => {
        const error = new Error('Something broke');
        expect(getErrorMessage(error)).toBe('Something broke');
    });

    it('returns unknown_error for non-Error values', () => {
        expect(getErrorMessage('string error')).toBe(API_ERROR_MESSAGES.unknown_error);
        expect(getErrorMessage(null)).toBe(API_ERROR_MESSAGES.unknown_error);
        expect(getErrorMessage(42)).toBe(API_ERROR_MESSAGES.unknown_error);
    });

    it('handles all known error codes', () => {
        for (const code of Object.keys(API_ERROR_MESSAGES)) {
            const error = new ApiRequestError(code, 'msg', 400);
            expect(getErrorMessage(error)).toBe(API_ERROR_MESSAGES[code]);
        }
    });
});

describe('getFieldErrors', () => {
    it('returns field errors from ApiRequestError with errors', () => {
        const error = new ApiRequestError('validation_error', 'Validation failed', 422, {
            email: ['Email is required', 'Email must be valid'],
            name: ['Name is required'],
        });
        const result = getFieldErrors(error);
        expect(result).toEqual({
            email: 'Email is required',
            name: 'Name is required',
        });
    });

    it('returns undefined for ApiRequestError without errors', () => {
        const error = new ApiRequestError('not_found', 'Not found', 404);
        expect(getFieldErrors(error)).toBeUndefined();
    });

    it('returns undefined for plain Error', () => {
        expect(getFieldErrors(new Error('test'))).toBeUndefined();
    });

    it('returns undefined for non-Error values', () => {
        expect(getFieldErrors('string')).toBeUndefined();
        expect(getFieldErrors(null)).toBeUndefined();
    });
});
