import { describe, it, expect } from "vitest";
import { renderHook, act } from "@testing-library/react";
import { useApiTokenForm } from "./useApiTokenForm";

describe("useApiTokenForm", () => {
    it("create mode initializes with default values", () => {
        const { result } = renderHook(() => useApiTokenForm({ mode: "create" }));

        expect(result.current.values.name).toBe("");
        expect(result.current.values.expiry).toBe("90");
        expect(result.current.values.abilities).toEqual([]);
        expect(result.current.values.fullAccess).toBe(true);
    });

    it("edit mode loadFrom populates fields from a token (full access)", () => {
        const { result } = renderHook(() => useApiTokenForm({ mode: "edit" }));

        act(() => {
            result.current.loadFrom({ name: "Token A", abilities: ["*"] });
        });

        expect(result.current.values.name).toBe("Token A");
        expect(result.current.values.fullAccess).toBe(true);
        expect(result.current.values.abilities).toEqual([]);
    });

    it("edit mode loadFrom populates fields from a token (granular abilities)", () => {
        const { result } = renderHook(() => useApiTokenForm({ mode: "edit" }));

        act(() => {
            result.current.loadFrom({
                name: "Token B",
                abilities: ["seminars:read", "users:read"],
            });
        });

        expect(result.current.values.fullAccess).toBe(false);
        expect(result.current.values.abilities).toEqual([
            "seminars:read",
            "users:read",
        ]);
    });

    it("reset() restores initial values", () => {
        const { result } = renderHook(() => useApiTokenForm({ mode: "create" }));

        act(() => {
            result.current.setName("Foo");
            result.current.setAbilities(["seminars:read"]);
            result.current.setExpiry("30");
            result.current.setFullAccess(false);
        });

        act(() => {
            result.current.reset();
        });

        expect(result.current.values.name).toBe("");
        expect(result.current.values.abilities).toEqual([]);
        expect(result.current.values.expiry).toBe("90");
        expect(result.current.values.fullAccess).toBe(true);
    });
});
