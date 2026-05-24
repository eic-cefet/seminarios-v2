import { fetchAdminApi, getCsrfCookie } from "./_base";

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
