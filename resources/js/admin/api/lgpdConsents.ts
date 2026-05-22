import { fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminLgpdConsent {
    type: string;
    granted: boolean;
    version: string | null;
    source: string | null;
    created_at: string | null;
}

export interface AdminLgpdExportRequest {
    id: number;
    status: string;
    created_at: string | null;
    completed_at: string | null;
    expires_at: string | null;
}

export interface AdminLgpdPayload {
    anonymization_requested_at: string | null;
    anonymized_at: string | null;
    consents: AdminLgpdConsent[];
    data_export_requests: AdminLgpdExportRequest[];
}

export const adminLgpdApi = {
    show: (userId: number) =>
        fetchAdminApi<{ data: AdminLgpdPayload }>(`/users/${userId}/lgpd`),

    export: (userId: number) =>
        fetchAdminApi<{ data: { data_export_request_id: number } }>(
            `/users/${userId}/lgpd/export`,
            { method: "POST" },
        ),

    anonymize: async (userId: number, reason: string) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(
            `/users/${userId}/lgpd/anonymize`,
            {
                method: "POST",
                body: JSON.stringify({ reason }),
            },
        );
    },
};
