import { render, screen, waitFor } from '@/test/test-utils';
import { createWorkshop } from '@/test/factories';
import Workshops from './Workshops';

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
    workshopsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { workshopsApi } from '@shared/api/client';

describe('Workshops', () => {
    it('renders "Workshops" heading', () => {
        render(<Workshops />);
        expect(screen.getByRole('heading', { name: /^workshops$/i })).toBeInTheDocument();
    });

    it('renders workshop cards after loading', async () => {
        const workshops = [
            createWorkshop({ name: 'Docker Workshop' }),
            createWorkshop({ name: 'Kubernetes Workshop' }),
        ];
        vi.mocked(workshopsApi.list).mockResolvedValue({ data: workshops });

        render(<Workshops />);

        await waitFor(() => {
            expect(screen.getByText('Docker Workshop')).toBeInTheDocument();
            expect(screen.getByText('Kubernetes Workshop')).toBeInTheDocument();
        });
    });

    it('shows empty state when no workshops', async () => {
        vi.mocked(workshopsApi.list).mockResolvedValue({ data: [] });

        render(<Workshops />);

        await waitFor(() => {
            expect(screen.getByText(/nenhum workshop encontrado/i)).toBeInTheDocument();
        });
    });
});
