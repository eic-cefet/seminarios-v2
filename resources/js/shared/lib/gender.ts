export type Gender = "masculine" | "feminine";

export function ifMasculine<T>(gender: Gender, masculine: T, feminine: T): T {
    return gender === "masculine" ? masculine : feminine;
}
