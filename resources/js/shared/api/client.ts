import type {
    Course,
    PaginatedResponse,
    Seminar,
    SeminarType,
    Subject,
    User,
    Workshop,
} from "@shared/types";
import { buildQueryString, getCookie, getCsrfCookie } from "./httpUtils";

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

async function fetchApi<T>(
    endpoint: string,
    options?: RequestInit,
): Promise<T> {
    const headers: Record<string, string> = {
        Accept: "application/json",
    };

    // Skip Content-Type for FormData (browser sets it with boundary automatically)
    if (!(options?.body instanceof FormData)) {
        headers["Content-Type"] = "application/json";
    }

    // Include XSRF token for non-GET requests
    const xsrfToken = getCookie("XSRF-TOKEN");
    if (xsrfToken) {
        headers["X-XSRF-TOKEN"] = xsrfToken;
    }

    const response = await fetch(`${app.API_URL}${endpoint}`, {
        headers,
        credentials: "same-origin",
        ...options,
    });

    if (!response.ok) {
        const data: ApiError = await response.json().catch(() => ({
            error: "unknown_error",
            message: response.statusText,
        }));
        throw new ApiRequestError(
            data.error,
            data.message,
            response.status,
            data.errors,
        );
    }

    return response.json();
}

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
        subjectId: number,
        params?: {
            upcoming?: boolean;
            direction?: "asc" | "desc";
            page?: number;
            per_page?: number;
        },
    ) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<PaginatedResponse<Seminar>>(
            `/subjects/${subjectId}/seminars${qs}`,
        );
    },
};

// Subjects
export const subjectsApi = {
    list: (params?: { sort?: "seminars" | "name"; limit?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<{ data: Subject[] }>(`/subjects${qs}`);
    },

    get: (id: number) => {
        return fetchApi<{ data: Subject }>(`/subjects/${id}`);
    },
};

// Workshops
export const workshopsApi = {
    list: () => {
        return fetchApi<{ data: Workshop[] }>("/workshops");
    },

    get: (id: number) => {
        return fetchApi<{ data: Workshop }>(`/workshops/${id}`);
    },

    seminars: (
        workshopId: number,
        params?: {
            upcoming?: boolean;
            direction?: "asc" | "desc";
            page?: number;
            per_page?: number;
        },
    ) => {
        const qs = buildQueryString(params ?? {});
        return fetchApi<PaginatedResponse<Seminar>>(
            `/workshops/${workshopId}/seminars${qs}`,
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
        return fetchApi<{ user: User }>("/auth/login", {
            method: "POST",
            body: JSON.stringify(data),
        });
    },

    register: async (data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        course_situation: "studying" | "graduated";
        course_role: "Aluno" | "Professor" | "Outro";
        course_id?: number;
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
        data: { score: number; comment?: string },
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

// Pending Evaluation type
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
        if (data.name) formData.append("name", data.name);
        if (data.email) formData.append("email", data.email);
        if (data.files) {
            data.files.forEach((file) => {
                formData.append("files[]", file);
            });
        }

        return fetchApi<{ message: string }>("/bug-report", {
            method: "POST",
            body: formData,
        });
    },
};
