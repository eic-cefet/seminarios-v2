import { getCookie, getCsrfCookie, buildQueryString } from './httpUtils';

describe('getCookie', () => {
    afterEach(() => {
        // Clear cookies
        document.cookie.split(';').forEach((c) => {
            document.cookie = c.trim().split('=')[0] + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
        });
    });

    it('returns cookie value by name', () => {
        document.cookie = 'test_cookie=hello_world';
        expect(getCookie('test_cookie')).toBe('hello_world');
    });

    it('returns null for non-existent cookie', () => {
        expect(getCookie('nonexistent')).toBeNull();
    });

    it('handles URL-encoded values', () => {
        document.cookie = 'encoded=%40value';
        expect(getCookie('encoded')).toBe('@value');
    });

    it('returns correct cookie when multiple cookies exist', () => {
        document.cookie = 'first=one';
        document.cookie = 'second=two';
        expect(getCookie('second')).toBe('two');
        expect(getCookie('first')).toBe('one');
    });
});

describe('getCsrfCookie', () => {
    it('fetches CSRF cookie from Sanctum endpoint', async () => {
        const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response());
        app.BASE_PATH = '';

        await getCsrfCookie();

        expect(fetchSpy).toHaveBeenCalledWith('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
        });
        fetchSpy.mockRestore();
    });

    it('includes BASE_PATH when set', async () => {
        const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response());
        app.BASE_PATH = '/app';

        await getCsrfCookie();

        expect(fetchSpy).toHaveBeenCalledWith('/app/sanctum/csrf-cookie', {
            credentials: 'same-origin',
        });

        app.BASE_PATH = '';
        fetchSpy.mockRestore();
    });
});

describe('buildQueryString', () => {
    it('builds query string from params', () => {
        const result = buildQueryString({ page: 1, search: 'test' });
        expect(result).toBe('?page=1&search=test');
    });

    it('skips undefined values', () => {
        const result = buildQueryString({ page: 1, search: undefined });
        expect(result).toBe('?page=1');
    });

    it('skips empty string values', () => {
        const result = buildQueryString({ page: 1, search: '' });
        expect(result).toBe('?page=1');
    });

    it('returns empty string for empty params', () => {
        expect(buildQueryString({})).toBe('');
    });

    it('handles boolean values', () => {
        const result = buildQueryString({ active: true });
        expect(result).toBe('?active=true');
    });
});
