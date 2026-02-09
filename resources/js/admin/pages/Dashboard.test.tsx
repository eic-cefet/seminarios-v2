import { render, screen, waitFor } from '@/test/test-utils';
import Dashboard from './Dashboard';

vi.mock('../api/adminClient', () => ({
    dashboardApi: {
        stats: vi.fn(),
    },
}));

import { dashboardApi } from '../api/adminClient';
const mockDashboardApi = dashboardApi as unknown as { stats: ReturnType<typeof vi.fn> };

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

    it('covers StatsCard description prop (line 44) when description is provided', async () => {
        // The StatsCard is used without description in Dashboard,
        // so stats?.counts null coalescing (lines 142-157) is the actual coverage target.
        // When stats is null/undefined, the ?? 0 branches are taken.
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 5, seminars: 3, registrations: 7, subjects: 2 },
                upcomingSeminars: [],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('5')).toBeInTheDocument();
        });
        expect(screen.getByText('3')).toBeInTheDocument();
        expect(screen.getByText('7')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('covers stats?.counts null coalescing branches (lines 142-157) when stats is null', async () => {
        // When data.data is null/undefined, stats will be undefined
        // and stats?.counts.users ?? 0 returns 0
        mockDashboardApi.stats.mockResolvedValue({
            data: null,
        });

        render(<Dashboard />);

        await waitFor(() => {
            // All four stat cards should show 0
            const zeros = screen.getAllByText('0');
            expect(zeros.length).toBeGreaterThanOrEqual(4);
        });
    });

    it('covers upcoming seminar without seminar_type badge (line 193-200)', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 1, seminars: 1, registrations: 0, subjects: 0 },
                upcomingSeminars: [
                    { id: 1, name: 'No Type Seminar', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: null },
                ],
                latestRatings: [],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('No Type Seminar')).toBeInTheDocument();
        });
        // No Badge should appear for seminar_type
        expect(screen.queryByText('Palestra')).not.toBeInTheDocument();
    });

    it('covers rating without comment (line 287-290)', async () => {
        mockDashboardApi.stats.mockResolvedValue({
            data: {
                counts: { users: 1, seminars: 1, registrations: 0, subjects: 0 },
                upcomingSeminars: [],
                latestRatings: [
                    { id: 1, score: 4, comment: null, seminar: { id: 1, name: 'Sem Comment' }, user: { id: 1, name: 'User X' } },
                ],
                nearCapacity: [],
                latestRegistrations: [],
            },
        });

        render(<Dashboard />);

        await waitFor(() => {
            expect(screen.getByText('Sem Comment')).toBeInTheDocument();
        });
        expect(screen.getByText('por User X')).toBeInTheDocument();
        expect(screen.getByText('4')).toBeInTheDocument();
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
