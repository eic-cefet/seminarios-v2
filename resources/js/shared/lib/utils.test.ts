import { cn, containsHTML, buildUrl, isSafeRedirect } from './utils';

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('foo', 'bar')).toBe('foo bar');
    });

    it('handles conditional classes', () => {
        expect(cn('base', false && 'hidden', 'visible')).toBe('base visible');
    });

    it('deduplicates tailwind classes', () => {
        expect(cn('p-4', 'p-2')).toBe('p-2');
    });

    it('returns empty string for no args', () => {
        expect(cn()).toBe('');
    });
});

describe('containsHTML', () => {
    it('returns true for strings with HTML tags', () => {
        expect(containsHTML('<p>Hello</p>')).toBe(true);
        expect(containsHTML('Text with <strong>bold</strong>')).toBe(true);
        expect(containsHTML('<div class="test">content</div>')).toBe(true);
    });

    it('returns false for plain text', () => {
        expect(containsHTML('Hello world')).toBe(false);
        expect(containsHTML('No tags here')).toBe(false);
    });

    it('returns false for non-tag angle brackets', () => {
        expect(containsHTML('5 < 10')).toBe(false);
    });
});

describe('isSafeRedirect', () => {
    it('accepts valid relative paths', () => {
        expect(isSafeRedirect('/')).toBe(true);
        expect(isSafeRedirect('/perfil')).toBe(true);
        expect(isSafeRedirect('/admin/seminars')).toBe(true);
        expect(isSafeRedirect('/perfil?tab=certificates')).toBe(true);
    });

    it('rejects protocol-relative URLs', () => {
        expect(isSafeRedirect('//evil.com')).toBe(false);
        expect(isSafeRedirect('//evil.com/path')).toBe(false);
    });

    it('rejects backslash-relative URLs', () => {
        expect(isSafeRedirect('/\\evil.com')).toBe(false);
    });

    it('rejects absolute URLs', () => {
        expect(isSafeRedirect('https://evil.com')).toBe(false);
        expect(isSafeRedirect('http://evil.com/login')).toBe(false);
    });

    it('rejects paths without leading slash', () => {
        expect(isSafeRedirect('evil.com')).toBe(false);
        expect(isSafeRedirect('relative/path')).toBe(false);
    });

    it('rejects non-string values', () => {
        expect(isSafeRedirect(null)).toBe(false);
        expect(isSafeRedirect(undefined)).toBe(false);
        expect(isSafeRedirect(123)).toBe(false);
        expect(isSafeRedirect('')).toBe(false);
    });
});

describe('buildUrl', () => {
    it('returns path as-is when no ROUTER_BASE', () => {
        app.ROUTER_BASE = '';
        expect(buildUrl('/login')).toBe('/login');
    });

    it('prepends ROUTER_BASE when set', () => {
        app.ROUTER_BASE = '/seminarios';
        expect(buildUrl('/login')).toBe('/seminarios/login');
    });

    it('handles path without leading slash', () => {
        app.ROUTER_BASE = '';
        expect(buildUrl('login')).toBe('login');
    });

    it('returns path as-is when base is set but path has no leading slash', () => {
        app.ROUTER_BASE = '/seminarios';
        expect(buildUrl('login')).toBe('login');
    });

    afterEach(() => {
        app.ROUTER_BASE = '';
    });
});
