import { describe, it, expect, beforeEach, vi } from "vitest";
import {
    COOKIE_CATEGORIES,
    clearCookieConsent,
    ensureAnonymousId,
    loadCookieConsent,
    saveCookieConsent,
    CONSENT_STORAGE_KEY,
    ANON_ID_STORAGE_KEY,
} from "./consent";

describe("consent storage", () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it("exposes the three cookie categories", () => {
        expect(COOKIE_CATEGORIES.map((c) => c.id)).toEqual([
            "essential",
            "functional",
            "analytics",
        ]);
    });

    it("returns null when no consent stored", () => {
        expect(loadCookieConsent()).toBeNull();
    });

    it("persists and loads consent state", () => {
        saveCookieConsent({
            essential: true,
            functional: true,
            analytics: false,
            version: "1.0",
            decided_at: "2026-04-23T10:00:00Z",
        });

        const loaded = loadCookieConsent();
        expect(loaded).not.toBeNull();
        expect(loaded?.analytics).toBe(false);
        expect(loaded?.functional).toBe(true);
    });

    it("generates and reuses a stable anonymous id", () => {
        const first = ensureAnonymousId();
        const second = ensureAnonymousId();
        expect(first).toBe(second);
        expect(first).toMatch(/^anon-/);
    });

    it("stores anonymous id under the shared key", () => {
        ensureAnonymousId();
        expect(localStorage.getItem(ANON_ID_STORAGE_KEY)).not.toBeNull();
    });

    it("persists consent under the shared key", () => {
        saveCookieConsent({
            essential: true,
            functional: false,
            analytics: false,
            version: "1.0",
            decided_at: "2026-04-23T10:00:00Z",
        });
        expect(localStorage.getItem(CONSENT_STORAGE_KEY)).not.toBeNull();
    });

    it("clears stored consent", () => {
        saveCookieConsent({
            essential: true,
            functional: true,
            analytics: true,
            version: "1.0",
            decided_at: "2026-04-23T10:00:00Z",
        });
        clearCookieConsent();
        expect(localStorage.getItem(CONSENT_STORAGE_KEY)).toBeNull();
    });

    it("returns null when localStorage.getItem throws", () => {
        const spy = vi.spyOn(Storage.prototype, "getItem").mockImplementation(() => {
            throw new Error("storage unavailable");
        });
        expect(loadCookieConsent()).toBeNull();
        spy.mockRestore();
    });
});
