import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";
import type { AdminUser } from "./users";

export interface AdminSeminar {
    id: number;
    name: string;
    slug: string;
    description?: string;
    scheduled_at: string;
    duration_minutes?: number;
    room_link?: string;
    active: boolean;
    created_by?: number;
    creator?: { id: number; name: string };
    seminar_location_id?: number;
    seminar_type_id?: number;
    workshop_id?: number;
    seminar_type?: { id: number; name: string } | null;
    location?: { id: number; name: string; max_vacancies: number } | null;
    workshop?: { id: number; name: string } | null;
    subjects?: { id: number; name: string }[];
    speakers?: AdminUser[];
    registrations_count?: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
}

export interface AdminSeminarType {
    id: number;
    name: string;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

export const seminarsApi = {
    list: (params?: {
        page?: number;
        search?: string;
        active?: boolean;
        upcoming?: boolean;
    }) => {
        const qs = buildQueryString({
            page: params?.page,
            search: params?.search,
            // active is tri-state: undefined (all), true ("1"), false ("0")
            active:
                params?.active !== undefined
                    ? params.active
                        ? "1"
                        : "0"
                    : undefined,
            upcoming: params?.upcoming,
        });
        return fetchAdminApi<PaginatedResponse<AdminSeminar>>(
            `/seminars${qs}`,
        );
    },

    get: (id: number) =>
        fetchAdminApi<{ data: AdminSeminar }>(`/seminars/${id}`),

    create: async (data: {
        name: string;
        description?: string;
        scheduled_at: string;
        duration_minutes: number;
        room_link?: string;
        active: boolean;
        seminar_location_id?: number;
        seminar_type_id?: number;
        workshop_id?: number;
        subject_names: string[];
        speaker_ids: number[];
    }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminSeminar }>(
            "/seminars",
            {
                method: "POST",
                body: JSON.stringify(data),
            },
        );
    },

    update: async (
        id: number,
        data: {
            name?: string;
            description?: string;
            scheduled_at?: string;
            duration_minutes?: number;
            room_link?: string;
            active?: boolean;
            seminar_location_id?: number;
            seminar_type_id?: number;
            workshop_id?: number;
            subject_names?: string[];
            speaker_ids?: number[];
        },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminSeminar }>(
            `/seminars/${id}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/seminars/${id}`, {
            method: "DELETE",
        });
    },
};
