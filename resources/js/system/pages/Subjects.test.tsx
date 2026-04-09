import { render, screen, waitFor } from '@/test/test-utils';
import { createSubject } from '@/test/factories';
import Subjects from './Subjects';

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
    subjectsApi: { list: vi.fn().mockResolvedValue({ data: [] }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { subjectsApi } from '@shared/api/client';

describe('Subjects', () => {
    it('renders "Tópicos" heading', () => {
        render(<Subjects />);
        expect(screen.getByRole('heading', { name: /^tópicos$/i })).toBeInTheDocument();
    });

    it('shows subjects after loading', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [createSubject({ name: 'Subject A' }), createSubject({ name: 'Subject B' })],
        });

        render(<Subjects />);

        await waitFor(() => {
            expect(screen.getByText('Subject A')).toBeInTheDocument();
            expect(screen.getByText('Subject B')).toBeInTheDocument();
        });
    });

    it('shows empty state when no subjects', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({ data: [] });

        render(<Subjects />);

        await waitFor(() => {
            expect(screen.getByText(/nenhum tópico encontrado/i)).toBeInTheDocument();
        });
    });

    it('shows 0 seminários when subject.seminarsCount is undefined', async () => {
        vi.mocked(subjectsApi.list).mockResolvedValue({
            data: [createSubject({ name: 'Topic Without Count', seminarsCount: undefined as any })],
        });

        render(<Subjects />);

        await waitFor(() => {
            expect(screen.getByText('Topic Without Count')).toBeInTheDocument();
        });

        expect(screen.getByText(/0\s+seminários/)).toBeInTheDocument();
    });
});
