export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
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

export interface Seminar {
    id: number;
    name: string;
    slug: string;
    description?: string;
    scheduledAt: string;
    endsAt?: string;
    durationMinutes?: number;
    roomLink?: string;
    active: boolean;
    isExpired: boolean;
    seminarType?: SeminarType;
    workshop?: Workshop;
    subjects?: Subject[];
    speakers?: Speaker[];
    location?: SeminarLocation;
    registrationsCount?: number;
    averageRating?: number;
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
