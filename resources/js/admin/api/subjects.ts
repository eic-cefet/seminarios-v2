import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminSubject {
    id: number;
    name: string;
    slug: string;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

export const subjectsApi = {
    list: (params?: { page?: number; search?: string }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminSubject>>(
            `/subjects${qs}`,
        );
    },

    get: (slug: string) =>
        fetchAdminApi<{ data: AdminSubject }>(`/subjects/${slug}`),

    create: async (data: { name: string }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminSubject }>(
            "/subjects",
            {
                method: "POST",
                body: JSON.stringify(data),
            },
        );
    },

    update: async (slug: string, data: { name: string }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminSubject }>(
            `/subjects/${slug}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (slug: string) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/subjects/${slug}`, {
            method: "DELETE",
        });
    },

    merge: async (data: {
        target_id: number;
        source_ids: number[];
        new_name?: string;
    }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminSubject }>(
            "/subjects/merge",
            {
                method: "POST",
                body: JSON.stringify(data),
            },
        );
    },
};
