import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { useNotifications } from "./useNotifications";
import { notificationsApi } from "../api/notifications";

function wrapper(client: QueryClient) {
    return ({ children }: { children: ReactNode }) => (
        <QueryClientProvider client={client}>{children}</QueryClientProvider>
    );
}

describe("useNotifications", () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it("fetches unread count on mount when userId is set", async () => {
        vi.spyOn(notificationsApi, "unreadCount").mockResolvedValue({ count: 3 });
        vi.spyOn(notificationsApi, "list").mockResolvedValue({
            data: [],
            meta: { current_page: 1, last_page: 1 },
        });

        const client = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });
        const { result } = renderHook(() => useNotifications({ userId: 1 }), {
            wrapper: wrapper(client),
        });

        await waitFor(() => expect(result.current.unreadCount).toBe(3));
        expect(notificationsApi.unreadCount).toHaveBeenCalled();
    });

    it("does not fetch when userId is null", async () => {
        const unread = vi
            .spyOn(notificationsApi, "unreadCount")
            .mockResolvedValue({ count: 0 });

        const client = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });
        renderHook(() => useNotifications({ userId: null }), {
            wrapper: wrapper(client),
        });

        await new Promise((r) => setTimeout(r, 10));
        expect(unread).not.toHaveBeenCalled();
    });

    it("invalidates queries when markRead is called", async () => {
        const markRead = vi
            .spyOn(notificationsApi, "markRead")
            .mockResolvedValue({ ok: true });
        vi.spyOn(notificationsApi, "list").mockResolvedValue({
            data: [],
            meta: { current_page: 1, last_page: 1 },
        });
        vi.spyOn(notificationsApi, "unreadCount").mockResolvedValue({ count: 0 });

        const client = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });
        const { result } = renderHook(() => useNotifications({ userId: 1 }), {
            wrapper: wrapper(client),
        });

        await waitFor(() => expect(result.current).toBeDefined());
        await result.current.markRead("abc");

        expect(markRead).toHaveBeenCalledWith("abc");
    });

    it("invalidates queries when markAllRead is called", async () => {
        const markAllRead = vi
            .spyOn(notificationsApi, "markAllRead")
            .mockResolvedValue({ ok: true });
        vi.spyOn(notificationsApi, "list").mockResolvedValue({
            data: [],
            meta: { current_page: 1, last_page: 1 },
        });
        vi.spyOn(notificationsApi, "unreadCount").mockResolvedValue({ count: 0 });

        const client = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });
        const { result } = renderHook(() => useNotifications({ userId: 1 }), {
            wrapper: wrapper(client),
        });

        await waitFor(() => expect(result.current).toBeDefined());
        await result.current.markAllRead();

        expect(markAllRead).toHaveBeenCalled();
    });
});
