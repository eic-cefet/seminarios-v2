import { cn, containsHTML, buildUrl, isSafeRedirect, stripMarkdown } from './utils';

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

describe('stripMarkdown', () => {
    it('removes heading hashes', () => {
        expect(stripMarkdown('# Resumo')).toBe('Resumo');
        expect(stripMarkdown('### Sub')).toBe('Sub');
    });

    it('removes bold and italic markers', () => {
        expect(stripMarkdown('Texto com **negrito** aqui')).toBe('Texto com negrito aqui');
        expect(stripMarkdown('Texto com __negrito__ e _itálico_')).toBe('Texto com negrito e itálico');
        expect(stripMarkdown('Texto com *itálico* aqui')).toBe('Texto com itálico aqui');
    });

    it('extracts text from links and images', () => {
        expect(stripMarkdown('Ver [docs](https://x.com/y)')).toBe('Ver docs');
        expect(stripMarkdown('![alt text](http://x/img.png)')).toBe('alt text');
    });

    it('removes inline and block code', () => {
        expect(stripMarkdown('Use `npm` para instalar')).toBe('Use npm para instalar');
        expect(stripMarkdown('antes\n```\nbloco\n```\ndepois')).toBe('antes depois');
    });

    it('removes list, blockquote, and rule markers', () => {
        expect(stripMarkdown('- item 1\n- item 2')).toBe('item 1 item 2');
        expect(stripMarkdown('1. primeiro\n2. segundo')).toBe('primeiro segundo');
        expect(stripMarkdown('> citação')).toBe('citação');
        expect(stripMarkdown('texto\n---\nmais')).toBe('texto mais');
    });

    it('collapses whitespace and trims', () => {
        expect(stripMarkdown('  texto   com    espaços  ')).toBe('texto com espaços');
    });

    it('returns empty string for empty input', () => {
        expect(stripMarkdown('')).toBe('');
    });

    it('handles a realistic seminar description', () => {
        const input = '# Resumo\nEste trabalho investiga a relação entre a **estrutura das redes de passe no futebol** e o desempenho.';
        expect(stripMarkdown(input)).toBe('Resumo Este trabalho investiga a relação entre a estrutura das redes de passe no futebol e o desempenho.');
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
