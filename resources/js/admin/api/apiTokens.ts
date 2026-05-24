import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminApiToken {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
}

export interface AdminApiTokenCreateResponse {
    message: string;
    data: {
        id: number;
        name: string;
        abilities: string[];
        token: string;
    };
}

export const apiTokensApi = {
    list: (params?: { page?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminApiToken>>(
            `/api-tokens${qs}`,
        );
    },

    create: async (data: {
        name: string;
        expires_in_days?: number | null;
        abilities?: string[];
    }) => {
        await getCsrfCookie();
        return fetchAdminApi<AdminApiTokenCreateResponse>("/api-tokens", {
            method: "POST",
            body: JSON.stringify(data),
        });
    },

    update: async (
        id: number,
        data: { name?: string; abilities?: string[] },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminApiToken }>(
            `/api-tokens/${id}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    regenerate: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<AdminApiTokenCreateResponse>(
            `/api-tokens/${id}/regenerate`,
            { method: "POST" },
        );
    },

    delete: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/api-tokens/${id}`, {
            method: "DELETE",
        });
    },

    abilities: () =>
        fetchAdminApi<{ data: string[] }>("/api-tokens/abilities"),
};
