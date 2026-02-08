import type {
    User,
    UserStudentData,
    Course,
    Subject,
    SeminarType,
    Speaker,
    SeminarLocation,
    Workshop,
    Seminar,
    Registration,
    PaginatedResponse,
} from '@shared/types';
import type { UserRegistration, UserCertificate, PendingEvaluation } from '@shared/api/client';

let idCounter = 1;
function nextId() {
    return idCounter++;
}

export function resetIdCounter() {
    idCounter = 1;
}

export function createUser(overrides: Partial<User> = {}): User {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `User ${id}`,
        email: `user${id}@example.com`,
        ...overrides,
    };
}

export function createCourse(overrides: Partial<Course> = {}): Course {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Course ${id}`,
        ...overrides,
    };
}

export function createStudentData(overrides: Partial<UserStudentData> = {}): UserStudentData {
    return {
        course_situation: 'studying',
        course_role: 'Aluno',
        course: createCourse(),
        ...overrides,
    };
}

export function createSubject(overrides: Partial<Subject> = {}): Subject {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Subject ${id}`,
        seminarsCount: 5,
        ...overrides,
    };
}

export function createSeminarType(overrides: Partial<SeminarType> = {}): SeminarType {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Type ${id}`,
        ...overrides,
    };
}

export function createSpeaker(overrides: Partial<Speaker> = {}): Speaker {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Speaker ${id}`,
        speakerData: {
            bio: 'A speaker bio',
            company: 'Test Company',
            position: 'Developer',
        },
        ...overrides,
    };
}

export function createLocation(overrides: Partial<SeminarLocation> = {}): SeminarLocation {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Location ${id}`,
        address: '123 Test St',
        ...overrides,
    };
}

export function createWorkshop(overrides: Partial<Workshop> = {}): Workshop {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Workshop ${id}`,
        description: 'A test workshop',
        seminarsCount: 3,
        ...overrides,
    };
}

export function createSeminar(overrides: Partial<Seminar> = {}): Seminar {
    const id = overrides.id ?? nextId();
    return {
        id,
        name: `Seminar ${id}`,
        slug: `seminar-${id}`,
        description: 'A test seminar description',
        scheduledAt: '2026-06-15T14:00:00Z',
        active: true,
        isExpired: false,
        seminarType: createSeminarType(),
        subjects: [createSubject()],
        speakers: [createSpeaker()],
        location: createLocation(),
        registrationsCount: 10,
        ...overrides,
    };
}

export function createRegistration(overrides: Partial<Registration> = {}): Registration {
    const id = overrides.id ?? nextId();
    return {
        id,
        userId: 1,
        seminarId: 1,
        present: false,
        ...overrides,
    };
}

export function createUserRegistration(overrides: Partial<UserRegistration> = {}): UserRegistration {
    const id = overrides.id ?? nextId();
    return {
        id,
        present: false,
        certificate_code: null,
        created_at: '2026-01-15T10:00:00Z',
        seminar: {
            id: nextId(),
            name: 'Test Seminar',
            slug: 'test-seminar',
            scheduled_at: '2026-06-15T14:00:00Z',
            is_expired: false,
            seminar_type: { id: 1, name: 'Palestra' },
            location: { id: 1, name: 'Room 101' },
        },
        ...overrides,
    };
}

export function createUserCertificate(overrides: Partial<UserCertificate> = {}): UserCertificate {
    const id = overrides.id ?? nextId();
    return {
        id,
        certificate_code: `CERT-${id}`,
        seminar: {
            id: nextId(),
            name: 'Test Seminar',
            slug: 'test-seminar',
            scheduled_at: '2026-06-15T14:00:00Z',
            seminar_type: { id: 1, name: 'Palestra' },
        },
        ...overrides,
    };
}

export function createPendingEvaluation(overrides: Partial<PendingEvaluation> = {}): PendingEvaluation {
    const id = overrides.id ?? nextId();
    return {
        id,
        seminar: {
            id: nextId(),
            name: 'Test Seminar',
            slug: 'test-seminar',
            scheduled_at: '2026-06-15T14:00:00Z',
            seminar_type: { id: 1, name: 'Palestra' },
            location: { id: 1, name: 'Room 101' },
        },
        ...overrides,
    };
}

export function createPaginatedResponse<T>(
    data: T[],
    overrides: Partial<PaginatedResponse<T>['meta']> = {},
): PaginatedResponse<T> {
    const meta = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: data.length,
        from: data.length > 0 ? 1 : 0,
        to: data.length,
        ...overrides,
    };
    return {
        data,
        meta,
        links: {
            first: '/api/resource?page=1',
            last: `/api/resource?page=${meta.last_page}`,
            prev: meta.current_page > 1 ? `/api/resource?page=${meta.current_page - 1}` : null,
            next: meta.current_page < meta.last_page ? `/api/resource?page=${meta.current_page + 1}` : null,
        },
    };
}
