import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, getCookie, getCsrfCookie } from "@shared/api/httpUtils";

const API_BASE = () => app.API_URL + "/admin";

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

export { getCsrfCookie };

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
    slug: string;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AdminSeminarType {
    id: number;
    name: string;
    seminars_count?: number;
    created_at: string;
    updated_at: string;
}

// Simplified types for dropdown lists
export interface LocationDropdownItem {
    id: number;
    name: string;
    max_vacancies: number;
}

export interface SeminarTypeDropdownItem {
    id: number;
    name: string;
}

export interface WorkshopDropdownItem {
    id: number;
    name: string;
}

export interface AdminWorkshop {
    id: number;
    name: string;
    slug: string;
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

export type AdminSentimentLabel =
    | "positive"
    | "negative"
    | "neutral"
    | "mixed"
    | null;

export interface AdminRating {
    id: number;
    score: number;
    comment?: string | null;
    sentiment?: string | null;
    sentiment_label?: AdminSentimentLabel;
    sentiment_analyzed_at?: string | null;
    user?: { id: number; name: string };
    seminar?: { id: number; name: string; slug: string };
    created_at: string;
    updated_at: string;
}

export interface AdminRatingSentimentsResponse {
    data: AdminRating[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    summary: {
        total_ratings: number;
        average_score: number | null;
        low_score_count: number;
    };
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

// Locations
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

// Subjects
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

// Workshops
export const workshopsApi = {
    list: (params?: { page?: number; search?: string }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminWorkshop>>(
            `/workshops${qs}`,
        );
    },

    get: (slug: string) =>
        fetchAdminApi<{ data: AdminWorkshop }>(`/workshops/${slug}`),

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
        slug: string,
        data: {
            name?: string;
            description?: string;
            seminar_ids?: number[];
        },
    ) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string; data: AdminWorkshop }>(
            `/workshops/${slug}`,
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    delete: async (slug: string) => {
        await getCsrfCookie();
        return fetchAdminApi<{ message: string }>(`/workshops/${slug}`, {
            method: "DELETE",
        });
    },

    searchSeminars: (params: { search?: string; workshop_id?: number }) => {
        const qs = buildQueryString(params);
        return fetchAdminApi<{ data: SeminarSearchResult[] }>(
            `/workshops/search-seminars${qs}`,
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
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<PaginatedResponse<AdminRegistration>>(
            `/registrations${qs}`,
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

// AI
export type AiAction =
    | "format_markdown"
    | "shorten"
    | "explain"
    | "formal"
    | "casual";

export const aiApi = {
    transformText: async (text: string, action: AiAction) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: { text: string } }>(
            "/ai/transform-text",
            {
                method: "POST",
                body: JSON.stringify({ text, action }),
            },
        );
    },

    suggestMergeName: async (names: string[]) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: { text: string } }>(
            "/ai/suggest-merge-name",
            {
                method: "POST",
                body: JSON.stringify({ names }),
            },
        );
    },
    suggestSubjectTags: async (subjectNames: string[]) => {
        await getCsrfCookie();
        return fetchAdminApi<{ data: { suggestions: string[] } }>(
            "/ai/suggest-subject-tags",
            {
                method: "POST",
                body: JSON.stringify({ subject_names: subjectNames }),
            },
        );
    },

    ratingSentiments: (params?: {
        page?: number;
        per_page?: number;
        search?: string;
        score?: number;
        sentiment_label?: Exclude<AdminSentimentLabel, null> | "null";
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<AdminRatingSentimentsResponse>(
            `/ai/rating-sentiments${qs}`,
        );
    },
};

// API Tokens
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

// Dropdown helpers (used by SeminarForm, SemestralReport, etc.)
export const dropdownApi = {
    seminarTypes: () =>
        fetchAdminApi<{ data: SeminarTypeDropdownItem[] }>("/seminar-types"),
    workshops: () =>
        fetchAdminApi<{ data: WorkshopDropdownItem[] }>("/workshops-dropdown"),
    locations: () =>
        fetchAdminApi<{ data: LocationDropdownItem[] }>("/locations-dropdown"),
    courses: () =>
        fetchAdminApi<{ data: { value: string | number; label: string }[] }>("/reports/courses"),
};

// Reports
export interface SemestralReportBrowserResponse {
    data: {
        name: string;
        email: string;
        course: string;
        total_minutes?: number;
        total_hours: number;
        presentations: {
            name: string;
            date: string;
            type: string | null;
            duration_minutes?: number;
        }[];
    }[];
    summary: {
        total_users: number;
        total_hours: number;
        semester: string;
    };
}

export type SemestralReportResponse =
    | SemestralReportBrowserResponse
    | { message: string };

export const reportsApi = {
    semestral: async (params: {
        semester: string;
        courses?: (string | number)[];
        types?: (string | number)[];
        situations?: string[];
        format: "browser" | "excel";
    }) => {
        await getCsrfCookie();
        const qs = new URLSearchParams();
        qs.set("semester", params.semester);
        params.courses?.forEach((c) => qs.append("courses[]", String(c)));
        params.types?.forEach((t) => qs.append("types[]", String(t)));
        params.situations?.forEach((s) => qs.append("situations[]", s));
        qs.set("format", params.format);
        return fetchAdminApi<SemestralReportResponse>(
            `/reports/semestral?${qs.toString()}`,
        );
    },
};

// Audit Logs
export interface AuditLogSummary {
    total: number;
    manual_count: number;
    system_count: number;
    top_events: Record<string, number>;
}

export const auditLogsApi = {
    summary: (params?: { days?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<{ data: AuditLogSummary }>(
            `/audit-logs/summary${qs}`,
        );
    },

    export: (params?: {
        days?: number;
        event_type?: string;
        event_name?: string;
        search?: string;
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<{ message: string }>(
            `/audit-logs/export${qs}`,
        );
    },

    eventNames: () =>
        fetchAdminApi<{ data: string[] }>("/audit-logs/event-names"),
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
