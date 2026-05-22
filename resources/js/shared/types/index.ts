export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    anonymization_requested_at?: string | null;
    student_data?: UserStudentData;
    roles?: string[];
    two_factor_enabled?: boolean;
}

export interface TwoFactorChallenge {
    challenge_token: string;
}

export interface TwoFactorEnableResponse {
    secret: string;
    qr_code_svg: string;
    recovery_codes: string[];
}

export interface TwoFactorDevice {
    id: number;
    label: string | null;
    ip: string | null;
    last_used_at: string | null;
    expires_at: string;
    created_at: string;
}

export interface UserStudentData {
    course_situation: "studying" | "graduated";
    course_role: "Aluno" | "Professor" | "Outro";
    course?: Course;
}

export interface Course {
    id: number;
    name: string;
}

export interface Subject {
    id: number;
    name: string;
    slug: string;
    seminarsCount?: number;
}

export interface SeminarType {
    id: number;
    name: string;
}

export interface Speaker {
    id: number;
    name: string;
    speakerData?: {
        bio?: string;
        company?: string;
        position?: string;
        linkedin?: string;
        github?: string;
    };
}

export interface SeminarLocation {
    id: number;
    name: string;
}

export interface Workshop {
    id: number;
    name: string;
    slug: string;
    description?: string;
    seminarsCount?: number;
    seminars?: Seminar[];
}

/**
 * Canonical shape of a public-API seminar summary.
 *
 * Emitted by `App\Http\Resources\SeminarResource` and a strict superset of the
 * smaller projections (UserRegistration, UserCertificate, PendingEvaluation,
 * PresenceLink), which pick a subset via `Pick<>` aliases in
 * `shared/api/client.ts`. Sub-object projections (where the backend uses
 * `$seminar->scheduled_at?->toISOString()`) override `scheduled_at` to a
 * nullable union; this base type matches the non-null contract of the
 * canonical `/api/seminars/*` endpoints.
 */
export interface PublicSeminarSummary {
    id: number;
    name: string;
    slug: string;
    scheduled_at: string;
    ends_at?: string;
    duration_minutes?: number;
    is_expired: boolean;
    seminar_type?: { id: number; name: string } | null;
    location?: { id: number; name: string } | null;
}

export interface Seminar extends PublicSeminarSummary {
    description?: string;
    room_link?: string;
    active: boolean;
    seminar_type?: SeminarType | null;
    workshop?: Workshop;
    subjects?: Subject[];
    speakers?: Speaker[];
    location?: SeminarLocation | null;
    registrations_count?: number;
    average_rating?: number;
}

export interface Registration {
    id: number;
    userId: number;
    seminarId: number;
    present: boolean;
    certificateCode?: string;
    user?: User;
    seminar?: Seminar;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
}
