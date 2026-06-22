import type { SeminarType } from '@shared/types';

type Gender = 'm' | 'f';

const FALLBACK = { gender: 'f' as Gender, noun: 'apresentação' };

function pick(gender: Gender, masculine: string, feminine: string): string {
    return gender === 'm' ? masculine : feminine;
}

/**
 * Generic Brazilian-Portuguese article/agreement helper for a presentation type.
 * The per-type gender + sentence noun come from the API (SeminarTypeResource);
 * a missing type falls back to the neutral feminine "apresentação", matching the
 * backend PresentationTypeGrammar fallback.
 */
export function presentationGrammar(type?: Pick<SeminarType, 'gender' | 'noun'> | null) {
    const gender: Gender = type?.gender ?? FALLBACK.gender;
    const noun = type?.noun ?? FALLBACK.noun;

    return {
        gender,
        noun,
        definite: `${pick(gender, 'o', 'a')} ${noun}`,
        withEm: `${pick(gender, 'no', 'na')} ${noun}`,
        withDe: `${pick(gender, 'do', 'da')} ${noun}`,
        demonstrative: `${pick(gender, 'este', 'esta')} ${noun}`,
        agree: (masculine: string, feminine: string) => pick(gender, masculine, feminine),
    };
}
