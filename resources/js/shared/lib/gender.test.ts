import { describe, it, expect } from "vitest";
import { ifMasculine, inlineTypeName, type Gender } from "./gender";

describe("ifMasculine", () => {
    it("returns the masculine value when gender is masculine", () => {
        expect(ifMasculine("masculine", "Novo", "Nova")).toBe("Novo");
    });

    it("returns the feminine value when gender is feminine", () => {
        expect(ifMasculine("feminine", "Novo", "Nova")).toBe("Nova");
    });

    it("preserves typed values via generics", () => {
        const gender: Gender = "feminine";
        const result = ifMasculine(gender, { article: "o" }, { article: "a" });
        expect(result.article).toBe("a");
    });
});

describe("inlineTypeName", () => {
    it("lowercases regular nouns", () => {
        expect(inlineTypeName("Dissertação")).toBe("dissertação");
    });

    it("lowercases the default Seminário", () => {
        expect(inlineTypeName("Seminário")).toBe("seminário");
    });

    it("preserves acronyms (all-uppercase alpha)", () => {
        expect(inlineTypeName("TCC")).toBe("TCC");
    });

    it("falls back to 'seminário' for null", () => {
        expect(inlineTypeName(null)).toBe("seminário");
    });

    it("falls back to 'seminário' for undefined", () => {
        expect(inlineTypeName(undefined)).toBe("seminário");
    });

    it("falls back to 'seminário' for empty string", () => {
        expect(inlineTypeName("")).toBe("seminário");
    });
});
