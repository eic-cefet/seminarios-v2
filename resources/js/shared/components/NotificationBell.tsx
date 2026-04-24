import { useState } from "react";
import { Bell } from "lucide-react";
import { useNotifications } from "../hooks/useNotifications";
import { buildUrl } from "../lib/utils";

interface Props {
    userId: number | null;
}

export function NotificationBell({ userId }: Props) {
    const [open, setOpen] = useState(false);
    const { notifications, unreadCount, refresh, markRead, markAllRead } =
        useNotifications({ userId });

    if (userId == null) {
        return null;
    }

    const toggle = () => {
        setOpen((prev) => {
            const next = !prev;
            if (next) {
                refresh();
            }
            return next;
        });
    };

    const handleClick = async (id: string, actionUrl: string | null) => {
        await markRead(id);
        if (actionUrl) {
            window.location.href = buildUrl(actionUrl);
        }
    };

    return (
        <div className="relative">
            <button
                type="button"
                aria-label={
                    unreadCount > 0
                        ? `${unreadCount} unread notifications`
                        : "Notifications"
                }
                onClick={toggle}
                className="relative p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
            >
                <Bell className="h-5 w-5" />
                {unreadCount > 0 && (
                    <span className="absolute top-0 right-0 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs font-semibold text-white bg-red-600 rounded-full">
                        {unreadCount > 99 ? "99+" : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-96 max-h-[70vh] overflow-y-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50">
                    <div className="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 className="font-semibold">Notificações</h3>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={() => markAllRead()}
                                className="text-sm text-blue-600 hover:underline"
                            >
                                Marcar todas como lidas
                            </button>
                        )}
                    </div>
                    {notifications.length === 0 ? (
                        <p className="p-6 text-center text-sm text-gray-500">
                            Nenhuma notificação.
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                            {notifications.map((n) => (
                                <li
                                    key={n.id}
                                    onClick={() =>
                                        handleClick(n.id, n.action_url)
                                    }
                                    className={`p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 ${
                                        n.read_at
                                            ? ""
                                            : "bg-blue-50 dark:bg-blue-950/30"
                                    }`}
                                >
                                    <p className="font-medium text-sm">
                                        {n.title}
                                    </p>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        {n.body}
                                    </p>
                                    <p className="mt-1 text-xs text-gray-500">
                                        {new Date(
                                            n.created_at,
                                        ).toLocaleString()}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
