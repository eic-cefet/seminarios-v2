export type NotificationCategory =
    | "certificate_ready"
    | "seminar_rescheduled"
    | "evaluation_due";

export interface InAppNotification {
    id: string;
    category: NotificationCategory;
    title: string;
    body: string;
    action_url: string | null;
    read_at: string | null;
    created_at: string;
}

export interface NotificationListResponse {
    data: InAppNotification[];
    meta: { current_page: number; last_page: number };
}
