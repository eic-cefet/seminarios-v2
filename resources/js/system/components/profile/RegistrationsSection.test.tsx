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
