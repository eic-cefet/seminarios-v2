import { useQuery, useQueryClient } from "@tanstack/react-query";
import { notificationsApi } from "../api/notifications";

const LIST_KEY = ["notifications"] as const;
const COUNT_KEY = ["notifications", "unread-count"] as const;

const POLL_INTERVAL_MS = 60_000;

export function useNotifications({ userId }: { userId: number | null }) {
    const queryClient = useQueryClient();

    const list = useQuery({
        queryKey: LIST_KEY,
        queryFn: () => notificationsApi.list(),
        enabled: userId != null,
        refetchOnWindowFocus: true,
    });

    const count = useQuery({
        queryKey: COUNT_KEY,
        queryFn: () => notificationsApi.unreadCount(),
        enabled: userId != null,
        refetchInterval: POLL_INTERVAL_MS,
        refetchIntervalInBackground: false,
        refetchOnWindowFocus: true,
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: LIST_KEY });
        queryClient.invalidateQueries({ queryKey: COUNT_KEY });
    };

    return {
        notifications: list.data?.data ?? [],
        unreadCount: count.data?.count ?? 0,
        isLoading: list.isLoading || count.isLoading,
        refresh: invalidate,
        markRead: async (id: string) => {
            await notificationsApi.markRead(id);
            invalidate();
        },
        markAllRead: async () => {
            await notificationsApi.markAllRead();
            invalidate();
        },
    };
}
