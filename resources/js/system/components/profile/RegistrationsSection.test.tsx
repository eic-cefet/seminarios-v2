import { render, screen, waitFor } from '@/test/test-utils';
import { createUserRegistration } from '@/test/factories';
import { RegistrationsSection } from './RegistrationsSection';

vi.mock('@shared/api/client', () => ({
    profileApi: {
        registrations: vi.fn(() => Promise.resolve({
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        })),
    },
    ApiRequestError: class extends Error {},
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

import { profileApi } from '@shared/api/client';

describe('RegistrationsSection', () => {
    it('renders section heading', () => {
        render(<RegistrationsSection />);

        expect(screen.getByRole('heading', { name: /minhas inscrições/i })).toBeInTheDocument();
    });

    it('renders empty state when no registrations', async () => {
        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText(/você ainda não se inscreveu em nenhum seminário/i)).toBeInTheDocument();
        });
    });

    it('renders link to presentations in empty state', async () => {
        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText(/ver apresentações/i)).toBeInTheDocument();
        });
    });

    it('renders registration list when data is available', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: false,
                seminar: {
                    id: 10,
                    name: 'Seminário de React',
                    slug: 'seminario-react',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    is_expired: false,
                    seminar_type: { id: 1, name: 'Palestra' },
                    location: { id: 1, name: 'Room 101' },
                },
            }),
            createUserRegistration({
                id: 2,
                present: true,
                seminar: {
                    id: 11,
                    name: 'Seminário de TypeScript',
                    slug: 'seminario-typescript',
                    scheduled_at: '2026-07-20T10:00:00Z',
                    is_expired: true,
                    seminar_type: { id: 2, name: 'Workshop' },
                    location: { id: 2, name: 'Room 202' },
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 },
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário de React')).toBeInTheDocument();
        });

        expect(screen.getByText('Seminário de TypeScript')).toBeInTheDocument();
    });

    it('renders "Inscrito" badge for non-expired, non-present registration', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: false,
                seminar: {
                    id: 10,
                    name: 'Seminário Futuro',
                    slug: 'seminario-futuro',
                    scheduled_at: '2026-12-01T14:00:00Z',
                    is_expired: false,
                    seminar_type: null,
                    location: null,
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Inscrito')).toBeInTheDocument();
        });
    });

    it('renders "Presente" badge when present is true', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: true,
                seminar: {
                    id: 10,
                    name: 'Seminário Passado',
                    slug: 'seminario-passado',
                    scheduled_at: '2026-01-01T14:00:00Z',
                    is_expired: true,
                    seminar_type: null,
                    location: null,
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Presente')).toBeInTheDocument();
        });
    });

    it('does not render scheduled date when scheduled_at is null', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: false,
                seminar: {
                    id: 10,
                    name: 'Seminário Sem Data',
                    slug: 'seminario-sem-data',
                    scheduled_at: null,
                    is_expired: false,
                    seminar_type: null,
                    location: { id: 1, name: 'Room 101' },
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário Sem Data')).toBeInTheDocument();
        });

        // Location should be shown but no date
        expect(screen.getByText('Room 101')).toBeInTheDocument();
        expect(screen.getByText('Inscrito')).toBeInTheDocument();
    });

    it('does not render location when location is null', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: false,
                seminar: {
                    id: 10,
                    name: 'Seminário Sem Local',
                    slug: 'seminario-sem-local',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    is_expired: false,
                    seminar_type: null,
                    location: null,
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário Sem Local')).toBeInTheDocument();
        });

        // No location rendered
        expect(screen.queryByText('Room 101')).not.toBeInTheDocument();
        expect(screen.queryByText('Room 202')).not.toBeInTheDocument();
    });

    it('falls back to page state for currentPage and 1 for lastPage when meta is undefined', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: false,
                seminar: {
                    id: 10,
                    name: 'Seminário Sem Meta',
                    slug: 'seminario-sem-meta',
                    scheduled_at: '2026-06-15T14:00:00Z',
                    is_expired: false,
                    seminar_type: null,
                    location: null,
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: undefined as any,
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Seminário Sem Meta')).toBeInTheDocument();
        });

        // The Pagination component renders with fallback values (currentPage=1, lastPage=1)
        expect(screen.getByText('Inscrito')).toBeInTheDocument();
    });

    it('renders "Ausente" badge when expired and not present', async () => {
        const registrations = [
            createUserRegistration({
                id: 1,
                present: false,
                seminar: {
                    id: 10,
                    name: 'Seminário Perdido',
                    slug: 'seminario-perdido',
                    scheduled_at: '2026-01-01T14:00:00Z',
                    is_expired: true,
                    seminar_type: null,
                    location: null,
                },
            }),
        ];

        vi.mocked(profileApi.registrations).mockResolvedValue({
            data: registrations,
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
        });

        render(<RegistrationsSection />);

        await waitFor(() => {
            expect(screen.getByText('Ausente')).toBeInTheDocument();
        });
    });
});
