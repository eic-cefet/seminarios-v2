import type {
    Course,
    PaginatedResponse,
    Seminar,
    SeminarType,
    Subject,
    TwoFactorChallenge,
    User,
    Workshop,
} from "@shared/types";
import {
    buildQueryString,
    createApiClient,
    getCookie,
    getCsrfCookie,
} from "./httpUtils";

export interface ApiError {
    error: string;
    message: string;
    errors?: Record<string, string[]>;
}

export class ApiRequestError extends Error {
    constructor(
        public readonly code: string,
        message: string,
        public readonly status: number,
        public readonly errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = "ApiRequestError";
    }
}

export { getCsrfCookie };

export const fetchApi = createApiClient({
    basePath: () => app.API_URL,
    errorFactory: (body, status) =>
        new ApiRequestError(body.error, body.message, status, body.errors),
    readXsrfToken: () => getCookie("XSRF-TOKEN"),
});

// Seminars
export const seminarsApi = {
    list: (params?: {
        type?: string;
        subject?: number;
        upcoming?: boolean;
        expired?: boolean;
        sort?: string;
        direction?: "asc" | "desc";
        page?: number;
        per_page?: number;
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<PaginatedResponse<Seminar>>(`/seminars${qs}`);
    },

    upcoming: () => {
        return fetchApi<{ data: Seminar[] }>("/seminars/upcoming");
    },

    get: (slug: string) => {
        return fetchApi<{ data: Seminar }>(`/seminars/${slug}`);
    },

    bySubject: (
        subjectSlug: string,
        params?: {
            upcoming?: boolean;
            direction?: "asc" | "desc";
            page?: number;
            per_page?: number;
        },
    ) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<PaginatedResponse<Seminar>>(
            `/subjects/${subjectSlug}/seminars${qs}`,
        );
    },
};

// Subjects
export const subjectsApi = {
    list: (params?: { sort?: "seminars" | "name"; limit?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<{ data: Subject[] }>(`/subjects${qs}`);
    },

    get: (slug: string) => {
        return fetchApi<{ data: Subject }>(`/subjects/${slug}`);
    },
};

// Workshops
export const workshopsApi = {
    list: () => {
        return fetchApi<{ data: Workshop[] }>("/workshops");
    },

    get: (slug: string) => {
        return fetchApi<{ data: Workshop }>(`/workshops/${slug}`);
    },

    seminars: (
        workshopSlug: string,
        params?: {
            upcoming?: boolean;
            direction?: "asc" | "desc";
            page?: number;
            per_page?: number;
        },
    ) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<PaginatedResponse<Seminar>>(
            `/workshops/${workshopSlug}/seminars${qs}`,
        );
    },
};

// Seminar Types
export const seminarTypesApi = {
    list: () => {
        return fetchApi<{ data: SeminarType[] }>("/seminar-types");
    },
};

// Courses
export const coursesApi = {
    list: () => {
        return fetchApi<{ data: Course[] }>("/courses");
    },
};

// Stats
export const statsApi = {
    get: () => {
        return fetchApi<{
            data: { subjects: number; seminars: number; workshops: number };
        }>("/stats");
    },
};

// Auth
export const authApi = {
    login: async (data: {
        email: string;
        password: string;
        remember?: boolean;
    }) => {
        await getCsrfCookie();
        return fetchApi<{ user: User } | { two_factor: TwoFactorChallenge }>(
            "/auth/login",
            {
                method: "POST",
                body: JSON.stringify(data),
            },
        );
    },

    register: async (data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        course_situation: "studying" | "graduated";
        course_role: "Aluno" | "Professor" | "Outro";
        course_id?: number;
        accepted_terms: boolean;
        accepted_privacy_policy: boolean;
    }) => {
        await getCsrfCookie();
        return fetchApi<{ user: User }>("/auth/register", {
            method: "POST",
            body: JSON.stringify(data),
        });
    },

    logout: async () => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/auth/logout", {
            method: "POST",
        });
    },

    me: () => {
        return fetchApi<{ user: User }>("/auth/me");
    },

    forgotPassword: async (email: string) => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/auth/forgot-password", {
            method: "POST",
            body: JSON.stringify({ email }),
        });
    },

    resetPassword: async (data: {
        token: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/auth/reset-password", {
            method: "POST",
            body: JSON.stringify(data),
        });
    },

    exchangeCode: async (code: string) => {
        await getCsrfCookie();
        return fetchApi<{ user: User }>("/auth/exchange", {
            method: "POST",
            body: JSON.stringify({ code }),
        });
    },
};

// Registration
export interface RegistrationStatus {
    registered: boolean;
    registration?: {
        id: number;
        created_at: string;
    } | null;
}

export const registrationApi = {
    status: (slug: string) => {
        return fetchApi<RegistrationStatus>(`/seminars/${slug}/registration`);
    },

    register: async (slug: string) => {
        await getCsrfCookie();
        return fetchApi<{
            message: string;
            registration: {
                id: number;
                seminar_id: number;
                created_at: string;
            };
        }>(`/seminars/${slug}/register`, {
            method: "POST",
        });
    },

    unregister: async (slug: string) => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>(`/seminars/${slug}/register`, {
            method: "DELETE",
        });
    },
};

// Profile
export interface UserRegistration {
    id: number;
    present: boolean;
    certificate_code: string | null;
    created_at: string;
    seminar: {
        id: number;
        name: string;
        slug: string;
        scheduled_at: string | null;
        ends_at?: string | null;
        duration_minutes?: number;
        is_expired: boolean;
        seminar_type: { id: number; name: string } | null;
        location: { id: number; name: string } | null;
    };
}

export interface UserCertificate {
    id: number;
    certificate_code: string;
    seminar: {
        id: number;
        name: string;
        slug: string;
        scheduled_at: string | null;
        seminar_type: { id: number; name: string } | null;
    };
}

export const profileApi = {
    get: () => {
        return fetchApi<{ user: User }>("/profile");
    },

    update: async (data: { name: string; email: string }) => {
        await getCsrfCookie();
        return fetchApi<{ message: string; user: User }>("/profile", {
            method: "PUT",
            body: JSON.stringify(data),
        });
    },

    updatePassword: async (data: {
        current_password: string;
        password: string;
        password_confirmation: string;
    }) => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/profile/password", {
            method: "PUT",
            body: JSON.stringify(data),
        });
    },

    registrations: (params?: { page?: number; per_page?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<{
            data: UserRegistration[];
            meta: {
                current_page: number;
                last_page: number;
                per_page: number;
                total: number;
            };
        }>(`/profile/registrations${qs}`);
    },

    certificates: (params?: { page?: number; per_page?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<{
            data: UserCertificate[];
            meta: {
                current_page: number;
                last_page: number;
                per_page: number;
                total: number;
            };
        }>(`/profile/certificates${qs}`);
    },

    updateStudentData: async (data: {
        course_id?: number | null;
        course_situation: string;
        course_role: string;
    }) => {
        await getCsrfCookie();
        return fetchApi<{ message: string; user: User }>(
            "/profile/student-data",
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
    },

    pendingEvaluations: () => {
        return fetchApi<{
            data: PendingEvaluation[];
        }>("/profile/pending-evaluations");
    },

    submitRating: async (
        seminarId: number,
        data: { score: number; comment?: string; ai_analysis_consent?: boolean },
    ) => {
        await getCsrfCookie();
        return fetchApi<{
            message: string;
            rating: { id: number; score: number; comment: string | null };
        }>(`/profile/ratings/${seminarId}`, {
            method: "POST",
            body: JSON.stringify(data),
        });
    },
};

export interface AlertPreference {
    newSeminarAlert: boolean;
    seminarTypeIds: number[];
    subjectIds: number[];
    seminarReminder7d: boolean;
    seminarReminder24h: boolean;
    evaluationPrompt: boolean;
    announcements: boolean;
    workshopAnnouncements: boolean;
    certificateReady: boolean;
    seminarRescheduled: boolean;
}

export const alertPreferencesApi = {
    get: async (): Promise<AlertPreference> => {
        const response = await fetchApi<{ data: AlertPreference }>(
            "/profile/alert-preferences",
        );
        return response.data;
    },

    update: async (data: {
        new_seminar_alert: boolean;
        seminar_type_ids: number[];
        subject_ids: number[];
        seminar_reminder_7d: boolean;
        seminar_reminder_24h: boolean;
        evaluation_prompt: boolean;
        announcements: boolean;
        workshop_announcements: boolean;
        certificate_ready: boolean;
        seminar_rescheduled: boolean;
    }): Promise<AlertPreference> => {
        await getCsrfCookie();
        const response = await fetchApi<{ message: string; data: AlertPreference }>(
            "/profile/alert-preferences",
            {
                method: "PUT",
                body: JSON.stringify(data),
            },
        );
        return response.data;
    },
};

export interface PendingEvaluation {
    id: number;
    seminar: {
        id: number;
        name: string;
        slug: string;
        scheduled_at: string | null;
        seminar_type: { id: number; name: string } | null;
        location: { id: number; name: string } | null;
    };
}

// Presence (public QR-code check-in)
export interface PresenceLinkData {
    seminar: {
        id: number;
        name: string;
        scheduled_at: string;
    };
    is_valid: boolean;
    expires_at: string;
}

export interface PresenceLinkResponse {
    data?: PresenceLinkData;
    message?: string;
    is_valid?: boolean;
    is_expired?: boolean;
    is_active?: boolean;
}

export const presenceApi = {
    get: (uuid: string) =>
        fetchApi<PresenceLinkResponse>(`/presence/${uuid}`),

    register: async (uuid: string) => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>(`/presence/${uuid}/register`, {
            method: "POST",
        });
    },
};

// LGPD Consents
export type ConsentType =
    | "terms_of_service"
    | "privacy_policy"
    | "cookies_functional"
    | "cookies_analytics"
    | "ai_sentiment_analysis";

export interface ConsentRecord {
    id: number;
    type: ConsentType;
    granted: boolean;
    version: string | null;
    source: string | null;
    created_at: string | null;
}

export interface RecordConsentInput {
    type: ConsentType;
    granted: boolean;
    version?: string;
    anonymous_id?: string;
}

export const consentsApi = {
    list: () => fetchApi<{ data: ConsentRecord[] }>("/consents"),
    record: async (input: RecordConsentInput) => {
        await getCsrfCookie();
        return fetchApi<{ data: ConsentRecord }>("/consents", {
            method: "POST",
            body: JSON.stringify(input),
        });
    },
};

// LGPD Data Privacy
export interface DataExportRequest {
    id: number;
    status: "queued" | "running" | "completed" | "failed";
    file_size_bytes: number | null;
    expires_at: string | null;
    completed_at: string | null;
    created_at: string | null;
    is_downloadable: boolean;
}

export const dataPrivacyApi = {
    listExports: () =>
        fetchApi<{ data: DataExportRequest[] }>("/profile/data-export"),
    requestExport: async () => {
        await getCsrfCookie();
        return fetchApi<{ data: DataExportRequest }>("/profile/data-export", {
            method: "POST",
        });
    },
    requestDeletion: async (password: string) => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>(
            "/profile/delete-request",
            {
                method: "POST",
                body: JSON.stringify({ password }),
            },
        );
    },
    confirmDeletion: async (token: string) => {
        await getCsrfCookie();
        return fetchApi<{ message: string; scheduled_for: string }>(
            "/profile/delete-confirm",
            {
                method: "POST",
                body: JSON.stringify({ token }),
            },
        );
    },
    cancelDeletion: async () => {
        await getCsrfCookie();
        return fetchApi<{ message: string }>("/profile/delete-cancel", {
            method: "POST",
        });
    },
};

// Bug Report
export const bugReportApi = {
    submit: async (data: {
        subject: string;
        message: string;
        name?: string;
        email?: string;
        files?: File[];
    }) => {
        await getCsrfCookie();

        const formData = new FormData();
        formData.append("subject", data.subject);
        formData.append("message", data.message);
        if (data.name) {
            formData.append("name", data.name);
        }
        if (data.email) {
            formData.append("email", data.email);
        }
        if (data.files) {
            for (const file of data.files) {
                formData.append("files[]", file);
            }
        }

        return fetchApi<{ message: string }>("/bug-report", {
            method: "POST",
            body: formData,
        });
    },
};
