import { render, screen, waitFor } from '@/test/test-utils';
import { createWorkshop, createSeminar } from '@/test/factories';
import WorkshopDetails from './WorkshopDetails';

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
    workshopsApi: {
        get: vi.fn().mockResolvedValue({ data: null }),
        seminars: vi.fn().mockResolvedValue({ data: [] }),
    },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useParams: vi.fn(() => ({ id: '1' })), useNavigate: vi.fn(() => vi.fn()) };
});

import { workshopsApi } from '@shared/api/client';

describe('WorkshopDetails', () => {
    it('renders workshop name after loading', async () => {
        const workshop = createWorkshop({ name: 'Advanced Docker' });
        vi.mocked(workshopsApi.get).mockResolvedValue({ data: workshop });
        vi.mocked(workshopsApi.seminars).mockResolvedValue({ data: [] });

        render(<WorkshopDetails />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: 'Advanced Docker' })).toBeInTheDocument();
        });
    });

    it('renders seminar list within workshop', async () => {
        const workshop = createWorkshop({ name: 'React Workshop' });
        const seminars = [
            createSeminar({ name: 'Session 1: Hooks' }),
            createSeminar({ name: 'Session 2: Context' }),
        ];
        vi.mocked(workshopsApi.get).mockResolvedValue({ data: workshop });
        vi.mocked(workshopsApi.seminars).mockResolvedValue({ data: seminars });

        render(<WorkshopDetails />);

        await waitFor(() => {
            expect(screen.getByText('Session 1: Hooks')).toBeInTheDocument();
            expect(screen.getByText('Session 2: Context')).toBeInTheDocument();
        });
    });

    it('shows not found state when workshop does not exist', async () => {
        vi.mocked(workshopsApi.get).mockResolvedValue({ data: null as any });
        vi.mocked(workshopsApi.seminars).mockResolvedValue({ data: [] });

        render(<WorkshopDetails />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /workshop não encontrado/i })).toBeInTheDocument();
        });
    });
});
