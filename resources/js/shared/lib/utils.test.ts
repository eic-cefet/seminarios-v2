import {
    buildAbsoluteUrl,
    buildUrl,
    cn,
    containsHTML,
    formatDate,
    formatDateTime,
    formatDateTimeLong,
    isExpired,
    isSafeRedirect,
    stripHtml,
    truncateText,
} from './utils';

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

describe('formatDate', () => {
    it('formats a date string to pt-BR', () => {
        const result = formatDate('2026-03-15T10:00:00Z');
        expect(result).toMatch(/15\/03\/2026/);
    });

    it('formats a Date object', () => {
        // Use midday to avoid timezone shifting the date
        const result = formatDate(new Date('2026-12-25T12:00:00Z'));
        expect(result).toMatch(/25\/12\/2026/);
    });
});

describe('formatDateTime', () => {
    it('formats a date string with time', () => {
        const result = formatDateTime('2026-03-15T14:30:00Z');
        expect(result).toMatch(/15\/03\/2026/);
        // Should contain time portion
        expect(result).toMatch(/\d{2}:\d{2}/);
    });

    it('formats a Date object with time', () => {
        const result = formatDateTime(new Date('2026-06-01T08:00:00Z'));
        expect(result).toMatch(/\d{2}\/\d{2}\/2026/);
    });
});

describe('formatDateTimeLong', () => {
    it('formats with long month name', () => {
        const result = formatDateTimeLong('2026-03-15T14:30:00Z');
        // Should contain a month name (not number)
        expect(result).toMatch(/\d{2}.*\d{4}/);
        expect(result).toMatch(/\d{2}:\d{2}/);
    });

    it('formats a Date object with long month name', () => {
        const result = formatDateTimeLong(new Date('2026-06-15T12:00:00Z'));
        expect(result).toMatch(/\d{2}.*\d{4}/);
        expect(result).toMatch(/\d{2}:\d{2}/);
    });
});

describe('isExpired', () => {
    it('returns true for past dates', () => {
        expect(isExpired('2020-01-01T00:00:00Z')).toBe(true);
    });

    it('returns false for future dates', () => {
        expect(isExpired('2099-12-31T23:59:59Z')).toBe(false);
    });

    it('accepts Date objects', () => {
        expect(isExpired(new Date('2020-01-01'))).toBe(true);
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

describe('stripHtml', () => {
    it('removes html tags and normalizes whitespace', () => {
        expect(stripHtml('<p>Hello <strong>world</strong></p>')).toBe(
            'Hello world',
        );
    });
});

describe('truncateText', () => {
    it('truncates long text with an ellipsis', () => {
        expect(truncateText('1234567890', 6)).toBe('12345…');
    });

    it('returns the full text when within the limit', () => {
        expect(truncateText('short', 10)).toBe('short');
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

describe('buildAbsoluteUrl', () => {
    it('uses APP BASE_PATH when configured', () => {
        app.BASE_PATH = 'https://seminarios.example.com/base';
        expect(buildAbsoluteUrl('/topicos')).toBe(
            'https://seminarios.example.com/base/topicos',
        );
    });

    it('falls back to the window origin when BASE_PATH is empty', () => {
        app.BASE_PATH = '';
        expect(buildAbsoluteUrl('/workshops')).toBe(
            `${window.location.origin}/workshops`,
        );
    });

    it('returns absolute urls unchanged', () => {
        expect(buildAbsoluteUrl('https://example.com/page')).toBe(
            'https://example.com/page',
        );
    });
});
