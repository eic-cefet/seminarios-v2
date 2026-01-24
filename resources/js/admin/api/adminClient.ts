import type { PaginatedResponse } from "@shared/types";

const API_BASE = () => app.API_URL + "/admin";
const BASE_PATH = () => app.BASE_PATH || "";

interface ApiError {
    error: string;
    message: string;
    errors?: Record<string, string[]>;
}

export class AdminApiError extends Error {
    constructor(
        public readonly code: string,
        message: string,
        public readonly status: number,
        public readonly errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = "AdminApiError";
    }
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(^|;\\s*)${name}=([^;]*)`));
    return match ? decodeURIComponent(match[2]) : null;
}

async function getCsrfCookie(): Promise<void> {
    const basePath = BASE_PATH();
    await fetch(`${basePath}/sanctum/csrf-cookie`, {
        credentials: "same-origin",
    });
}

async function fetchAdminApi<T>(
    endpoint: string,
    options?: RequestInit,
): Promise<T> {
    const headers: Record<string, string> = {
        Accept: "application/json",
        "Content-Type": "application/json",
    };

    const xsrfToken = getCookie("XSRF-TOKEN");
    if (xsrfToken) {
        headers["X-XSRF-TOKEN"] = xsrfToken;
    }

    const response = await fetch(`${API_BASE()}${endpoint}`, {
        headers,
        credentials: "same-origin",
        ...options,
    });

    if (!response.ok) {
        const data: ApiError = await response.json().catch(() => ({
            error: "unknown_error",
            message: response.statusText,
        }));
        throw new AdminApiError(
            data.error,
            data.message,
            response.status,
            data.errors,
        );
    }

    return response.json();
}

// Types
export interface AdminUser {
    id: number;
    name: string;
    email: string;
    username?: string;
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

export interface AdminSeminar {
    id: number;
    name: string;
    slug: string;
    description?: string;
    scheduled_at: string;
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

export interface AdminLocation {
    id: number;
    name: string;
    max_vacancies: number;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AdminSubject {
    id: number;
    name: string;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AdminWorkshop {
    id: number;
    name: string;
    description?: string;
    seminars_count?: number;
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

export interface AdminRegistration {
    id: number;
    present: boolean;
    reminder_sent: boolean;
    certificate_code?: string;
    certificate_sent: boolean;
    user?: { id: number; name: string; email: string };
    seminar?: { id: number; name: string; slug: string; scheduled_at: string };
    created_at: string;
    updated_at: string;
}

export interface AdminRating {
    id: number;
    score: number;
    comment?: string;
    user?: { id: number; name: string };
    seminar?: { id: number; name: string; slug: string };
    created_at: string;
    updated_at: string;
}

export interface DashboardStats {
    upcomingSeminars: AdminSeminar[];
    latestRatings: AdminRating[];
    nearCapacity: AdminSeminar[];
    latestRegistrations: AdminRegistration[];
    counts: {
        users: number;
        seminars: number;
        registrations: number;
        subjects: number;
    };
}

// Dashboard
export const dashboardApi = {
    stats: () => fetchAdminApi<{ data: DashboardStats }>("/dashboard/stats"),
    seminars: () => fetchAdminApi<{ data: AdminSeminar[] }>("/seminars"),
};

// Users
export const usersApi = {
    list: (params?: {
        page?: number;
        trashed?: boolean;
        search?: string;
        role?: string;
    }) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.trashed) searchParams.set("trashed", "1");
        if (params?.search) searchParams.set("search", params.search);
        if (params?.role) searchParams.set("role", params.role);
        const query = searchParams.toString();
        return fetchAdminApi<PaginatedResponse<AdminUser>>(
            `/users${query ? `?${query}` : ""}`,
        );
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

// Locations
export const locationsApi = {
    list: (params?: { page?: number }) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set("page", params.page.toString());
        const query = searchParams.toString();
        return fetchAdminApi<PaginatedResponse<AdminLocation>>(
            `/locations${query ? `?${query}` : ""}`,
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

// Subjects
export const subjectsApi = {
    list: (params?: { page?: number; search?: string }) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.search) searchParams.set("search", params.search);
        const query = searchParams.toString();
        return fetchAdminApi<PaginatedResponse<AdminSubject>>(
            `/subjects${query ? `?${query}` : ""}`,
        );
    },

    get: (id: number) =>
        fetchAdminApi<{ data: AdminSubject }>(`/subjects/${id}`),

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

    update: async (id: number, data: { name: string }) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminSubject }>(
            `/subjects/${id}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/subjects/${id}`, {
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

// Workshops
export const workshopsApi = {
    list: (params?: { page?: number; search?: string }) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.search) searchParams.set("search", params.search);
        const query = searchParams.toString();
        return fetchAdminApi<PaginatedResponse<AdminWorkshop>>(
            `/workshops${query ? `?${query}` : ""}`,
        );
    },

    get: (id: number) =>
        fetchAdminApi<{ data: AdminWorkshop }>(`/workshops/${id}`),

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
        id: number,
        data: {
            name?: string;
            description?: string;
            seminar_ids?: number[];
        },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminWorkshop }>(
            `/workshops/${id}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/workshops/${id}`, {
            method: "DELETE",
        });
    },

    searchSeminars: (params: { search?: string; workshop_id?: number }) => {
        const searchParams = new URLSearchParams();
        if (params.search) searchParams.set("search", params.search);
        if (params.workshop_id)
            searchParams.set("workshop_id", params.workshop_id.toString());
        const query = searchParams.toString();
        return fetchAdminApi<{ data: SeminarSearchResult[] }>(
            `/workshops/search-seminars${query ? `?${query}` : ""}`,
        );
    },
};

// Registrations
export const registrationsApi = {
    list: (params?: {
        page?: number;
        seminar_id?: number;
        search?: string;
    }) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.seminar_id)
            searchParams.set("seminar_id", params.seminar_id.toString());
        if (params?.search) searchParams.set("search", params.search);
        const query = searchParams.toString();
        return fetchAdminApi<PaginatedResponse<AdminRegistration>>(
            `/registrations${query ? `?${query}` : ""}`,
        );
    },

    togglePresence: async (id: number) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminRegistration }>(
            `/registrations/${id}/presence`,
            {
                method: "PATCH",
            },
        );
    },
};

// Seminars
export const seminarsApi = {
    list: (params?: {
        page?: number;
        search?: string;
        active?: boolean;
        upcoming?: boolean;
    }) => {
        const searchParams = new URLSearchParams();
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.search) searchParams.set("search", params.search);
        if (params?.active !== undefined)
            searchParams.set("active", params.active ? "1" : "0");
        if (params?.upcoming) searchParams.set("upcoming", "1");
        const query = searchParams.toString();
        return fetchAdminApi<PaginatedResponse<AdminSeminar>>(
            `/seminars${query ? `?${query}` : ""}`,
        );
    },

    get: (id: number) =>
        fetchAdminApi<{ data: AdminSeminar }>(`/seminars/${id}`),

    create: async (data: {
        name: string;
        description?: string;
        scheduled_at: string;
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

// Presence Links (QR Code)
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
