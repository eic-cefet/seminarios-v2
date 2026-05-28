// [letters/apostrophes/hyphens] of length 2+, optionally followed by a single
// trailing period to allow academic honorifics like "Dr.", "Dra.", "Prof.",
// "Profa." which are common on Brazilian certificates. Single-letter initials
// with a period ("C.") still fail because the {2,} count applies to the
// letter run, not the period.
const PART_REGEX = /^[\p{L}'\-]{2,}\.?$/u;

export const FULL_NAME_MESSAGE =
    "Informe seu nome completo (nome e sobrenome).";

export function isFullName(value: string): boolean {
    const parts = value.trim().split(/\s+/u);
    if (parts.length < 2) {
        return false;
    }
    return parts.every((part) => PART_REGEX.test(part));
}
