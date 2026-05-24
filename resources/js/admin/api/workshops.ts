import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminWorkshop {
    id: number;
    name: string;
    slug: string;
    description?: string;
    seminars_count?: number;
    announcement_sent_at: string | null;
    seminars?: {
        id: number;
        name: string;
        slug: string;
        scheduled_at: string;
    }[];
    created_at: string;
    updated_at: string;
}

export interface SeminarSearchResult {
    id: number;
    name: string;
    slug: string;
    scheduled_at: string;
    workshop_id: number | null;
}

export const workshopsApi = {
    list: (params?: { page?: number; search?: string }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminWorkshop>>(
            `/workshops${qs}`,
        );
    },

    get: (slug: string) =>
        fetchAdminApi<{ data: AdminWorkshop }>(`/workshops/${slug}`),

    create: async (data: {
        name: string;
        description?: string;
        seminar_ids?: number[];
    }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminWorkshop }>(
            "/workshops",
            {
                method: "POST",
                body: JSON.stringify(data),
            },
        );
    },

    update: async (
        slug: string,
        data: {
            name?: string;
            description?: string;
            seminar_ids?: number[];
        },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminWorkshop }>(
            `/workshops/${slug}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (slug: string) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/workshops/${slug}`, {
            method: "DELETE",
        });
    },

    searchSeminars: (params: { search?: string; workshop_id?: number }) => {
        const qs = buildQueryString(params);
        return fetchAdminApi<{ data: SeminarSearchResult[] }>(
            `/workshops/search-seminars${qs}`,
        );
    },

    announce: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminWorkshop }>(
            `/workshops/${id}/announce`,
            { method: "POST" },
        );
    },
};
