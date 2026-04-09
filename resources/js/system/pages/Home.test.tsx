import { render, screen, waitFor } from '@/test/test-utils';
import { createSeminar, createSubject } from '@/test/factories';
import Home from './Home';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/api/client', () => ({
    seminarsApi: { upcoming: vi.fn().mockResolvedValue({ data: [] }) },
    subjectsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
    statsApi: { get: vi.fn().mockResolvedValue({ data: { subjects: 5, seminars: 10, workshops: 3 } }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { seminarsApi, subjectsApi, statsApi } from '@shared/api/client';

describe('Home', () => {
    beforeEach(() => {
        vi.mocked(seminarsApi.upcoming).mockResolvedValue({ data: [] });
        vi.mocked(subjectsApi.list).mockResolvedValue({ data: [] });
        vi.mocked(statsApi.get).mockResolvedValue({ data: { subjects: 5, seminars: 10, workshops: 3 } });
    });

    it('renders "Seminários EIC" hero', () => {
        render(<Home />);
        expect(screen.getByRole('heading', { name: /seminários eic/i })).toBeInTheDocument();
    });

    it('shows stats after loading', async () => {
        render(<Home />);

        await waitFor(() => {
            expect(screen.getByText('10')).toBeInTheDocument();
        });
    });

    it('renders seminar cards after loading', async () => {
        const seminar = createSeminar({ name: 'Test Seminar' });
        vi.mocked(seminarsApi.upcoming).mockResolvedValue({ data: [seminar] });

        render(<Home />);

        await waitFor(() => {
            expect(screen.getByText('Test Seminar')).toBeInTheDocument();
        });
    });

    it('renders subject cards with links when subjects are available', async () => {
        const subjects = [
            createSubject({ id: 1, name: 'Inteligência Artificial', seminarsCount: 12 }),
            createSubject({ id: 2, name: 'Segurança da Informação', seminarsCount: 8 }),
        ];
        vi.mocked(subjectsApi.list).mockResolvedValue({ data: subjects });

        render(<Home />);

        await waitFor(() => {
            expect(screen.getByText('Inteligência Artificial')).toBeInTheDocument();
            expect(screen.getByText('Segurança da Informação')).toBeInTheDocument();
        });

        // Check subject links
        const aiLink = screen.getByText('Inteligência Artificial').closest('a');
        expect(aiLink).toHaveAttribute('href', '/topico/1');

        const secLink = screen.getByText('Segurança da Informação').closest('a');
        expect(secLink).toHaveAttribute('href', '/topico/2');

        // Check seminars count is displayed
        expect(screen.getByText('12 seminários')).toBeInTheDocument();
        expect(screen.getByText('8 seminários')).toBeInTheDocument();
    });

    it('renders subject with seminarsCount as 0 when undefined', async () => {
        const subjects = [
            createSubject({ id: 1, name: 'No Count Subject', seminarsCount: undefined as any }),
        ];
        vi.mocked(subjectsApi.list).mockResolvedValue({ data: subjects });

        render(<Home />);

        await waitFor(() => {
            expect(screen.getByText('No Count Subject')).toBeInTheDocument();
        });

        // The ?? 0 fallback should display "0 seminários"
        expect(screen.getByText('0 seminários')).toBeInTheDocument();
    });

    it('shows "Nenhum seminário agendado" when no upcoming seminars', async () => {
        vi.mocked(seminarsApi.upcoming).mockResolvedValue({ data: [] });

        render(<Home />);

        await waitFor(() => {
            expect(screen.getByText(/nenhum seminário agendado no momento/i)).toBeInTheDocument();
        });
    });
});
