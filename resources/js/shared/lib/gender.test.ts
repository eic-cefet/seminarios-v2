import { describe, it, expect } from "vitest";
import { ifMasculine, type Gender } from "./gender";

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
