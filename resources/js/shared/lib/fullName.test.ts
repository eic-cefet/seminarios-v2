import { describe, expect, it } from "vitest";
import { isFullName } from "./fullName";

describe("isFullName", () => {
    it("rejects empty strings", () => {
        expect(isFullName("")).toBe(false);
    });

    it("rejects a single word", () => {
        expect(isFullName("Maria")).toBe(false);
    });

    it("rejects a name with a single-letter surname", () => {
        expect(isFullName("Maria S")).toBe(false);
    });

    it("accepts a simple full name", () => {
        expect(isFullName("Maria Silva")).toBe(true);
    });

    it("accepts accented names", () => {
        expect(isFullName("João da Conceição")).toBe(true);
    });

    it("accepts hyphenated and apostrophed names", () => {
        expect(isFullName("Anne-Marie D'Souza")).toBe(true);
    });

    it("trims surrounding whitespace before validating", () => {
        expect(isFullName("   Maria Silva   ")).toBe(true);
    });

    it("accepts academic honorifics with a trailing period", () => {
        expect(isFullName("Dra. Mariana Costa Silva")).toBe(true);
        expect(isFullName("Dr. João Silva")).toBe(true);
        expect(isFullName("Prof. Maria Souza")).toBe(true);
        expect(isFullName("Profa. Ana Lima")).toBe(true);
    });

    it("still rejects single-letter initials with a period", () => {
        expect(isFullName("Maria C. Silva")).toBe(false);
    });
});
