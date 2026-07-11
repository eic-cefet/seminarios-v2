import type { PaginatedResponse } from "@shared/types";
import { buildQueryString, fetchAdminApi } from "./_base";

export interface AdminStudentListItem {
    id: number;
    name: string;
    email: string;
    course: string;
    course_situation?: "studying" | "graduated";
}

export interface AdminStudentListResponse {
    data: AdminStudentListItem[];
    meta: PaginatedResponse<unknown>["meta"];
    summary: { semester: string };
}

export type StudentRegistrationStatus = "attended" | "missed" | "upcoming";

export interface StudentRegistration {
    id: number;
    present: boolean;
    status: StudentRegistrationStatus;
    certificate_code?: string | null;
    seminar: {
        id: number;
        name: string;
        scheduled_at: string;
        duration_minutes: number;
        seminar_type: string | null;
    };
}

export interface StudentCertificate {
    id: number;
    certificate_code: string;
    seminar_name: string;
    download_url: string;
}

export interface StudentTypeBreakdown {
    type: string;
    attended: number;
    missed: number;
    hours: number;
}

export interface AdminStudentDashboard {
    student: {
        id: number;
        name: string;
        email: string;
        course: string;
        course_situation?: "studying" | "graduated";
    };
    semester: string;
    totals: {
        attended: number;
        missed: number;
        upcoming: number;
        hours_attended: number;
    };
    by_type: StudentTypeBreakdown[];
    registrations: StudentRegistration[];
    certificates: StudentCertificate[];
}

export const studentsApi = {
    list: (params: { semester?: string; search?: string; page?: number }) => {
        const qs = buildQueryString(params);
        return fetchAdminApi<AdminStudentListResponse>(`/students${qs}`);
    },

    dashboard: (userId: number, semester: string) => {
        const qs = buildQueryString({ semester });
        return fetchAdminApi<{ data: AdminStudentDashboard }>(
            `/students/${userId}/dashboard${qs}`,
        );
    },

    aiSummary: (userId: number, semester: string) => {
        const qs = buildQueryString({ semester });
        return fetchAdminApi<{ data: { summary: string } }>(
            `/students/${userId}/ai-summary${qs}`,
        );
    },
};
