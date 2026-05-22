import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminLocation {
    id: number;
    name: string;
    max_vacancies: number;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

export const locationsApi = {
    list: (params?: { page?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminLocation>>(
            `/locations${qs}`,
        );
    },

    get: (id: number) =>
        fetchAdminApi<{ data: AdminLocation }>(`/locations/${id}`),

    create: async (data: { name: string; max_vacancies: number }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminLocation }>(
            "/locations",
            {
                method: "POST",
                body: JSON.stringify(data),
            },
        );
    },

    update: async (
        id: number,
        data: { name?: string; max_vacancies?: number },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminLocation }>(
            `/locations/${id}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/locations/${id}`, {
            method: "DELETE",
        });
    },
};
