import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { notificationsApi } from "./notifications";

vi.mock("./httpUtils", async (importOriginal) => {
    const actual = await importOriginal<typeof import("./httpUtils")>();
    return {
        ...actual,
        getCsrfCookie: vi.fn(async () => undefined),
        getCookie: vi.fn(() => null),
    };
});

describe("notificationsApi", () => {
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

    it("list calls GET /notifications", async () => {
        await notificationsApi.list();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/notifications"),
            expect.objectContaining({ credentials: "same-origin" }),
        );
    });

    it("unreadCount calls GET /notifications/unread-count", async () => {
        await notificationsApi.unreadCount();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/notifications/unread-count"),
            expect.any(Object),
        );
    });

    it("markRead POSTs to /notifications/{id}/read", async () => {
        await notificationsApi.markRead("abc");
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/notifications/abc/read"),
            expect.objectContaining({ method: "POST" }),
        );
    });

    it("markAllRead POSTs to /notifications/read-all", async () => {
        await notificationsApi.markAllRead();
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("/notifications/read-all"),
            expect.objectContaining({ method: "POST" }),
        );
    });
});
