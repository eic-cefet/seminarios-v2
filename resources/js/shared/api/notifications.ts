import { fetchApi } from "./client";
import type {
    InAppNotification,
    NotificationListResponse,
} from "../types/notifications";

export const notificationsApi = {
    list: (): Promise<NotificationListResponse> =>
        fetchApi("/notifications"),

    unreadCount: (): Promise<{ count: number }> =>
        fetchApi("/notifications/unread-count"),

    markRead: (id: string): Promise<{ ok: true }> =>
        fetchApi(`/notifications/${id}/read`, { method: "POST" }),

    markAllRead: (): Promise<{ ok: true }> =>
        fetchApi("/notifications/read-all", { method: "POST" }),
};

export type { InAppNotification };
