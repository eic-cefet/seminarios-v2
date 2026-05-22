import { fetchAdminApi } from "./_base";
import type { AdminSeminar } from "./seminars";
import type { AdminRating } from "./ratings";
import type { AdminRegistration } from "./registrations";

export interface DashboardStats {
    upcomingSeminars: AdminSeminar[];
    latestRatings: AdminRating[];
    nearCapacity: AdminSeminar[];
    latestRegistrations: AdminRegistration[];
    counts: {
        users: number;
        seminars: number;
        registrations: number;
        subjects: number;
    };
}

export const dashboardApi = {
    stats: () => fetchAdminApi<{ data: DashboardStats }>("/dashboard/stats"),
    seminars: () => fetchAdminApi<{ data: AdminSeminar[] }>("/seminars"),
};
