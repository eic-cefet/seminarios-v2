import { render, screen, waitFor } from '@/test/test-utils';
import Dashboard from './Dashboard';

vi.mock('../api/adminClient', () => ({
    dashboardApi: {
        stats: vi.fn(),
    },
}));

import { dashboardApi } from '../api/adminClient';
const mockDashboardApi = dashboardApi as { stats: ReturnType<typeof vi.fn> };

describe('Dashboard', () => {
    it('shows loading state initially', () => {
        mockDashboardApi.stats.mockReturnValue(new Promise(() => {}));
        render(<Dashboard />);
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
    });

    it('renders stats cards after loading', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 50, seminars: 20, registrations: 100, subjects: 10 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('50')).toBeInTheDocument();
        });

        expect(screen.getByText('20')).toBeInTheDocument();
        expect(screen.getByText('100')).toBeInTheDocument();
        expect(screen.getByText('10')).toBeInTheDocument();
    });

    it('shows error state', async () => {
        mockDashboardApi.stats.mockRejectedValue(new Error('Failed'));

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Erro ao carregar estatisticas')).toBeInTheDocument();
        });
    });

    it('renders empty states for sections', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 0, seminars: 0, registrations: 0, subjects: 0 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum seminario agendado')).toBeInTheDocument();
        });
    });

    it('renders all empty state messages', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 0, seminars: 0, registrations: 0, subjects: 0 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Nenhum seminario agendado')).toBeInTheDocument();
        });
        expect(screen.getByText('Nenhum seminario perto da capacidade')).toBeInTheDocument();
        expect(screen.getByText('Nenhuma avaliacao ainda')).toBeInTheDocument();
        expect(screen.getByText('Nenhuma inscricao ainda')).toBeInTheDocument();
    });

    it('renders upcoming seminars when available', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 10, seminars: 5, registrations: 20, subjects: 3 },
                upcomingSeminars: [
                    { id: 1, name: 'Upcoming Seminar', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: { id: 1, name: 'Palestra' } },
                ],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Upcoming Seminar')).toBeInTheDocument();
        });
        expect(screen.getByText('Palestra')).toBeInTheDocument();
    });

    it('renders near capacity seminars when available', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 10, seminars: 5, registrations: 20, subjects: 3 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [
                    { id: 1, name: 'Almost Full', registrations_count: 45, location: { id: 1, name: 'Sala A', max_vacancies: 50 } },
                ],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Almost Full')).toBeInTheDocument();
        });
        expect(screen.getByText('45/50')).toBeInTheDocument();
    });

    it('renders latest ratings when available', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 10, seminars: 8, registrations: 20, subjects: 3 },
                upcomingSeminars: [],
                latestRatings: [
                    { id: 1, score: 5, comment: 'Great talk', seminar: { id: 1, name: 'Rated Seminar' }, user: { id: 1, name: 'Reviewer' } },
                ],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Rated Seminar')).toBeInTheDocument();
        });
        expect(screen.getByText('por Reviewer')).toBeInTheDocument();
        expect(screen.getByText('Great talk')).toBeInTheDocument();
    });

    it('renders latest registrations when available', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 10, seminars: 5, registrations: 20, subjects: 3 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [
                    { id: 1, user: { name: 'New Student' }, seminar: { name: 'Popular Seminar' }, created_at: '2026-02-01T10:00:00Z' },
                ],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('New Student')).toBeInTheDocument();
        });
        expect(screen.getByText('Popular Seminar')).toBeInTheDocument();
    });

    it('renders section titles', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 0, seminars: 0, registrations: 0, subjects: 0 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Proximas Apresentacoes')).toBeInTheDocument();
        });
        expect(screen.getByText('Perto da Capacidade')).toBeInTheDocument();
        expect(screen.getByText('Ultimas Avaliacoes')).toBeInTheDocument();
        expect(screen.getByText('Inscricoes Recentes')).toBeInTheDocument();
    });

    it('renders stat card titles', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 1, seminars: 2, registrations: 3, subjects: 4 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Usuarios')).toBeInTheDocument();
        });
        expect(screen.getByText('Seminarios')).toBeInTheDocument();
        expect(screen.getByText('Inscricoes')).toBeInTheDocument();
        expect(screen.getByText('Tópicos')).toBeInTheDocument();
    });
});
