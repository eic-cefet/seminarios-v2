import { fetchAdminApi, getCsrfCookie } from "./_base";

export const presenceLinkApi = {
    get: (seminarId: number) =>
        fetchAdminApi<{
            data: {
                id: number;
                uuid: string;
                active: boolean;
                expires_at: string;
                is_expired: boolean;
                is_valid: boolean;
                url: string;
                png_url: string;
                qr_code: string;
            } | null;
        }>(`/seminars/${seminarId}/presence-link`),

    create: async (seminarId: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{
            message: string;
            data: {
                id: number;
                uuid: string;
                active: boolean;
                expires_at: string;
                is_expired: boolean;
                is_valid: boolean;
                url: string;
                png_url: string;
                qr_code: string;
            };
        }>(`/seminars/${seminarId}/presence-link`, {
            method: "POST",
        });
    },

    toggle: async (seminarId: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{
            message: string;
            data: {
                id: number;
                uuid: string;
                active: boolean;
                expires_at: string;
                is_expired: boolean;
                is_valid: boolean;
            };
        }>(`/seminars/${seminarId}/presence-link/toggle`, {
            method: "PATCH",
        });
    },
};
