import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminRegistration {
    id: number;
    present: boolean;
    reminder_sent: boolean;
    certificate_code?: string;
    certificate_sent: boolean;
    user?: { id: number; name: string; email: string };
    seminar?: { id: number; name: string; slug: string; scheduled_at: string };
    created_at: string;
    updated_at: string;
}

export const registrationsApi = {
    list: (params?: {
        page?: number;
        seminar_id?: number;
        search?: string;
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminRegistration>>(
            `/registrations${qs}`,
        );
    },

    togglePresence: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminRegistration }>(
            `/registrations/${id}/presence`,
            {
                method: "PATCH",
            },
        );
    },
};
