import { describe, expect, it } from "vitest";
import { registerSchema } from "./Register.schema";

const base = {
    email: "maria@example.test",
    password: "Password!1234",
    passwordConfirmation: "Password!1234",
    courseSituation: "studying",
    courseRole: "Aluno",
    courseId: "",
    acceptedTerms: true,
    acceptedPrivacy: true,
};

describe("registerSchema name validation", () => {
    it("rejects a one-word name", () => {
        const result = registerSchema.safeParse({ ...base, name: "Maria" });
        expect(result.success).toBe(false);
    });

    it("accepts a full name", () => {
        const result = registerSchema.safeParse({
            ...base,
            name: "Maria Silva",
        });
        expect(result.success).toBe(true);
    });
});
