import type {
    TwoFactorDevice,
    TwoFactorEnableResponse,
    User,
} from "@shared/types";
import { fetchApi } from "./client";
import { getCsrfCookie } from "./httpUtils";

export const twoFactorApi = {
    enable: async (): Promise<TwoFactorEnableResponse> => {
        await getCsrfCookie();
        return fetchApi<TwoFactorEnableResponse>("/profile/two-factor/enable", {
            method: "POST",
        });
    },
    confirm: async (code: string): Promise<{ message: string }> => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/profile/two-factor/confirm", {
            method: "POST",
            body: JSON.stringify({ code }),
        });
    },
    regenerateRecoveryCodes: async (): Promise<{ recovery_codes: string[] }> => {
        await getCsrfCookie();
        return fetchApi<{ recovery_codes: string[] }>(
            "/profile/two-factor/recovery-codes",
            { method: "POST" },
        );
    },
    disable: async (): Promise<{ message: string }> => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/profile/two-factor", {
            method: "DELETE",
        });
    },
    listDevices: () =>
        fetchApi<{ devices: TwoFactorDevice[] }>("/profile/two-factor/devices"),
    revokeDevice: async (id: number): Promise<{ message: string }> => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>(
            `/profile/two-factor/devices/${id}`,
            { method: "DELETE" },
        );
    },
    challenge: async (input: {
        challenge_token: string;
        code?: string;
        recovery_code?: string;
        remember_device?: boolean;
    }): Promise<{ user: User }> => {
        await getCsrfCookie();
        return fetchApi<{ user: User }>("/auth/two-factor-challenge", {
            method: "POST",
            body: JSON.stringify(input),
        });
    },
};
