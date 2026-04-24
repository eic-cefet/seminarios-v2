import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { twoFactorApi } from "./twoFactorApi";

vi.mock("./httpUtils", async (importOriginal) => {
    const actual =
        await importOriginal<typeof import("./httpUtils")>();
    return {
        ...actual,
        getCsrfCookie: vi.fn(async () => undefined),
        getCookie: vi.fn(() => null),
    };
});

describe("twoFactorApi", () => {
    beforeEach(() => {
        vi.stubGlobal(
            "fetch",
            vi.fn(
                async () =>
                    new Response(JSON.stringify({ ok: true }), { status: 200 }),
            ),
        );
        (globalThis as unknown as { app: { API_URL: string } }).app = {
            API_URL: "http://api.test",
        };
    });
    afterEach(() => vi.unstubAllGlobals());

    it("enable posts to /profile/two-factor/enable", async () => {
        await twoFactorApi.enable();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/profile/two-factor/enable"),
            expect.objectContaining({ method: "POST" }),
        );
    });

    it("confirm posts the code", async () => {
        await twoFactorApi.confirm("123456");
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/profile/two-factor/confirm"),
            expect.objectContaining({
                method: "POST",
                body: JSON.stringify({ code: "123456" }),
            }),
        );
    });

    it("disable sends DELETE", async () => {
        await twoFactorApi.disable();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/profile/two-factor"),
            expect.objectContaining({ method: "DELETE" }),
        );
    });

    it("regenerateRecoveryCodes posts to /recovery-codes", async () => {
        await twoFactorApi.regenerateRecoveryCodes();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/profile/two-factor/recovery-codes"),
            expect.objectContaining({ method: "POST" }),
        );
    });

    it("listDevices fetches", async () => {
        await twoFactorApi.listDevices();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/profile/two-factor/devices"),
            expect.objectContaining({ credentials: "same-origin" }),
        );
    });

    it("revokeDevice sends DELETE with id", async () => {
        await twoFactorApi.revokeDevice(42);
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/profile/two-factor/devices/42"),
            expect.objectContaining({ method: "DELETE" }),
        );
    });

    it("challenge posts to /auth/two-factor-challenge", async () => {
        await twoFactorApi.challenge({
            challenge_token: "x",
            code: "123456",
            remember_device: true,
        });
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/auth/two-factor-challenge"),
            expect.objectContaining({ method: "POST" }),
        );
    });
});
