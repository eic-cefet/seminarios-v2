import { describe, expect, it } from 'vitest';
import { presentationGrammar } from './presentationGrammar';

describe('presentationGrammar', () => {
    it('derives forms for a masculine type', () => {
        const g = presentationGrammar({ gender: 'm', noun: 'seminário' });
        expect(g.definite).toBe('o seminário');
        expect(g.withEm).toBe('no seminário');
        expect(g.withDe).toBe('do seminário');
        expect(g.demonstrative).toBe('este seminário');
        expect(g.agree('reagendado', 'reagendada')).toBe('reagendado');
    });

    it('derives forms for a feminine type', () => {
        const g = presentationGrammar({ gender: 'f', noun: 'dissertação' });
        expect(g.definite).toBe('a dissertação');
        expect(g.withEm).toBe('na dissertação');
        expect(g.agree('novo', 'nova')).toBe('nova');
    });

    it('falls back to neutral feminine "apresentação" when type is missing', () => {
        const g = presentationGrammar(null);
        expect(g.definite).toBe('a apresentação');
        expect(g.withEm).toBe('na apresentação');
        expect(g.noun).toBe('apresentação');
        expect(presentationGrammar(undefined).withDe).toBe('da apresentação');
    });
});
