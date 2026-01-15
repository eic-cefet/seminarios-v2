import type {
    PaginatedResponse,
    Seminar,
    SeminarType,
    Subject,
    Workshop,
} from "@shared/types";

const API_BASE = window.API_URL || "/api";

async function fetchApi<T>(
    endpoint: string,
    options?: RequestInit
): Promise<T> {
    const response = await fetch(`${API_BASE}${endpoint}`, {
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
        },
        ...options,
    });

    if (!response.ok) {
        throw new Error(`API error: ${response.status} ${response.statusText}`);
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
        const searchParams = new URLSearchParams();
        if (params?.type) searchParams.set("type", params.type);
        if (params?.subject)
            searchParams.set("subject", params.subject.toString());
        if (params?.upcoming) searchParams.set("upcoming", "1");
        if (params?.expired) searchParams.set("expired", "1");
        if (params?.sort) searchParams.set("sort", params.sort);
        if (params?.direction) searchParams.set("direction", params.direction);
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.per_page)
            searchParams.set("per_page", params.per_page.toString());

        const query = searchParams.toString();
        return fetchApi<PaginatedResponse<Seminar>>(
            `/seminars${query ? `?${query}` : ""}`
        );
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
        }
    ) => {
        const searchParams = new URLSearchParams();
        if (params?.upcoming) searchParams.set("upcoming", "1");
        if (params?.direction) searchParams.set("direction", params.direction);
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.per_page)
            searchParams.set("per_page", params.per_page.toString());

        const query = searchParams.toString();
        return fetchApi<PaginatedResponse<Seminar>>(
            `/subjects/${subjectId}/seminars${query ? `?${query}` : ""}`
        );
    },
};

// Subjects
export const subjectsApi = {
    list: (params?: { sort?: "seminars" | "name"; limit?: number }) => {
        const searchParams = new URLSearchParams();
        if (params?.sort) searchParams.set("sort", params.sort);
        if (params?.limit) searchParams.set("limit", params.limit.toString());
        const query = searchParams.toString();
        return fetchApi<{ data: Subject[] }>(`/subjects${query ? `?${query}` : ""}`);
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
        }
    ) => {
        const searchParams = new URLSearchParams();
        if (params?.upcoming) searchParams.set("upcoming", "1");
        if (params?.direction) searchParams.set("direction", params.direction);
        if (params?.page) searchParams.set("page", params.page.toString());
        if (params?.per_page)
            searchParams.set("per_page", params.per_page.toString());

        const query = searchParams.toString();
        return fetchApi<PaginatedResponse<Seminar>>(
            `/workshops/${workshopId}/seminars${query ? `?${query}` : ""}`
        );
    },
};

// Seminar Types
export const seminarTypesApi = {
    list: () => {
        return fetchApi<{ data: SeminarType[] }>("/seminar-types");
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
