import { fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminEnvSecretsStatus {
    secret_id: string | null;
    region: string | null;
    access_key_id_set: boolean;
    secret_access_key_set: boolean;
}

export interface AdminEnvSecretsUpdatePayload {
    secret_id: string;
    region?: string;
    access_key_id?: string;
    secret_access_key?: string;
}

export interface AdminEnvSecretsUpdateResult {
    applied: boolean;
    keys: string[];
    count: number;
}

export const envSecretsApi = {
    get: () =>
        fetchAdminApi<{ data: AdminEnvSecretsStatus }>("/system/env-secrets"),
    update: async (data: AdminEnvSecretsUpdatePayload) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: AdminEnvSecretsUpdateResult }>(
            "/system/env-secrets",
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },
};
