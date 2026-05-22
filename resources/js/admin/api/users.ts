import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    student_data?: {
        course_name?: string;
        course_situation?: string;
        course_role?: string;
    } | null;
    speaker_data?: {
        slug?: string;
        institution?: string;
        description?: string;
    } | null;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
}

export const usersApi = {
    list: (params?: {
        page?: number;
        trashed?: boolean;
        search?: string;
        role?: string;
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminUser>>(`/users${qs}`);
    },

    get: (id: number) => fetchAdminApi<{ data: AdminUser }>(`/users/${id}`),

    create: async (data: {
        name: string;
        email: string;
        password: string;
        role?: "admin" | "teacher" | "user";
        student_data?: {
            course_name?: string;
            course_situation?: string;
            course_role?: string;
        };
        speaker_data?: { institution?: string; description?: string };
    }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminUser }>("/users", {
            method: "POST",
            body: JSON.stringify(data),
        });
    },

    update: async (
        id: number,
        data: {
            name?: string;
            email?: string;
            password?: string;
            role?: string;
            student_data?: {
                course_name?: string;
                course_situation?: string;
                course_role?: string;
            } | null;
            speaker_data?: {
                slug?: string;
                institution?: string;
                description?: string;
            } | null;
        },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminUser }>(
            `/users/${id}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/users/${id}`, {
            method: "DELETE",
        });
    },

    restore: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminUser }>(
            `/users/${id}/restore`,
            {
                method: "POST",
            },
        );
    },
};
