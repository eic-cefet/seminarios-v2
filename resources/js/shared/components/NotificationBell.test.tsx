import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { NotificationBell } from "./NotificationBell";
import * as hook from "../hooks/useNotifications";

type MockReturn = ReturnType<typeof hook.useNotifications>;

function mockHook(overrides: Partial<MockReturn>): MockReturn {
    return {
        notifications: [],
        unreadCount: 0,
        isLoading: false,
        refresh: vi.fn(),
        markRead: vi.fn(),
        markAllRead: vi.fn(),
        ...overrides,
    };
}

function renderBell(userId: number | null = 1) {
    const client = new QueryClient();
    return render(
        <QueryClientProvider client={client}>
            <NotificationBell userId={userId} />
        </QueryClientProvider>,
    );
}

describe("NotificationBell", () => {
    it("renders nothing when userId is null", () => {
        vi.spyOn(hook, "useNotifications").mockReturnValue(mockHook({}));
        const { container } = renderBell(null);
        expect(container).toBeEmptyDOMElement();
    });

    it("renders unread count badge when count > 0", () => {
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({ unreadCount: 5 }),
        );
        renderBell();
        expect(screen.getByLabelText(/5 unread/i)).toBeInTheDocument();
        expect(screen.getByText("5")).toBeInTheDocument();
    });

    it("caps the badge at 99+", () => {
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({ unreadCount: 120 }),
        );
        renderBell();
        expect(screen.getByText("99+")).toBeInTheDocument();
    });

    it("hides badge when count is 0", () => {
        vi.spyOn(hook, "useNotifications").mockReturnValue(mockHook({}));
        renderBell();
        expect(screen.queryByText("0")).not.toBeInTheDocument();
    });

    it("refreshes on open and marks read when an item is clicked", async () => {
        const markRead = vi.fn();
        const refresh = vi.fn();
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({
                notifications: [
                    {
                        id: "abc",
                        category: "certificate_ready",
                        title: "T",
                        body: "B",
                        action_url: "/x",
                        read_at: null,
                        created_at: "2026-04-24T00:00:00Z",
                    },
                ],
                unreadCount: 1,
                refresh,
                markRead,
            }),
        );
        renderBell();
        fireEvent.click(screen.getByRole("button", { name: /notifications/i }));
        expect(refresh).toHaveBeenCalled();
        fireEvent.click(screen.getByText("T"));
        expect(markRead).toHaveBeenCalledWith("abc");
    });

    it("calls markAllRead when the 'Marcar todas como lidas' button is clicked", () => {
        const markAllRead = vi.fn();
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({ unreadCount: 3, markAllRead }),
        );
        renderBell();
        fireEvent.click(screen.getByRole("button", { name: /notifications/i }));
        fireEvent.click(screen.getByText("Marcar todas como lidas"));
        expect(markAllRead).toHaveBeenCalled();
    });

    it("does not refresh when closing the dropdown", () => {
        const refresh = vi.fn();
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({ refresh }),
        );
        renderBell();
        const trigger = screen.getByRole("button", { name: /notifications/i });
        fireEvent.click(trigger);
        expect(refresh).toHaveBeenCalledTimes(1);
        fireEvent.click(trigger);
        expect(refresh).toHaveBeenCalledTimes(1);
    });

    it("marks read without navigating when action_url is null", async () => {
        const markRead = vi.fn();
        const originalHref = window.location.href;
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({
                notifications: [
                    {
                        id: "n1",
                        category: "evaluation_due",
                        title: "Avaliar",
                        body: "Avalie o seminário",
                        action_url: null,
                        read_at: null,
                        created_at: "2026-04-24T00:00:00Z",
                    },
                ],
                unreadCount: 1,
                markRead,
            }),
        );
        renderBell();
        fireEvent.click(screen.getByRole("button", { name: /notifications/i }));
        fireEvent.click(screen.getByText("Avaliar"));
        await Promise.resolve();
        expect(markRead).toHaveBeenCalledWith("n1");
        expect(window.location.href).toBe(originalHref);
    });

    it("renders read items without the unread highlight", () => {
        vi.spyOn(hook, "useNotifications").mockReturnValue(
            mockHook({
                notifications: [
                    {
                        id: "n2",
                        category: "certificate_ready",
                        title: "Lido",
                        body: "Já lido",
                        action_url: null,
                        read_at: "2026-04-24T00:00:00Z",
                        created_at: "2026-04-23T00:00:00Z",
                    },
                ],
                unreadCount: 0,
            }),
        );
        renderBell();
        fireEvent.click(screen.getByRole("button", { name: /notifications/i }));
        const item = screen.getByText("Lido").closest("li");
        expect(item?.className).not.toContain("bg-blue-50");
    });
});
