const PART_REGEX = /^[\p{L}'\-]{2,}$/u;

export const FULL_NAME_MESSAGE =
    "Informe seu nome completo (nome e sobrenome).";

export function isFullName(value: string): boolean {
    const parts = value.trim().split(/\s+/u);
    if (parts.length < 2) {
        return false;
    }
    return parts.every((part) => PART_REGEX.test(part));
}
