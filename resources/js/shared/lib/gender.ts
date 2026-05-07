export type Gender = "masculine" | "feminine";

export function ifMasculine<T>(gender: Gender, masculine: T, feminine: T): T {
    return gender === "masculine" ? masculine : feminine;
}

/**
 * Returns the type name lowercased for inline use, preserving all-uppercase
 * acronyms (e.g., "TCC" stays "TCC", "Dissertação" -> "dissertação").
 * Falls back to "seminário" when the name is null/undefined/empty.
 */
export function inlineTypeName(name: string | null | undefined): string {
    if (!name) {
        return "seminário";
    }
    const alpha = name.replace(/[^\p{L}]/gu, "");
    if (alpha && alpha === alpha.toUpperCase()) {
        return name;
    }
    return name.toLowerCase();
}
