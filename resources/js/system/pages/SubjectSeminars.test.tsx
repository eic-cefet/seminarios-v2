import { render, screen, waitFor } from '@/test/test-utils';
import { createSubject, createSeminar } from '@/test/factories';
import SubjectSeminars from './SubjectSeminars';

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
    subjectsApi: { get: vi.fn().mockResolvedValue({ data: null }), list: vi.fn().mockResolvedValue({ data: [] }) },
    seminarsApi: { bySubject: vi.fn().mockResolvedValue({ data: [] }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useParams: vi.fn(() => ({ id: '1' })) };
});

import { subjectsApi, seminarsApi } from '@shared/api/client';

describe('SubjectSeminars', () => {
    it('renders subject name heading after loading', async () => {
        const subject = createSubject({ name: 'Machine Learning' });
        vi.mocked(subjectsApi.get).mockResolvedValue({ data: subject });
        vi.mocked(seminarsApi.bySubject).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 }, links: { first: '', last: '', prev: null, next: null } });

        render(<SubjectSeminars />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: 'Machine Learning' })).toBeInTheDocument();
        });
    });

    it('renders seminar cards', async () => {
        const subject = createSubject({ name: 'AI Topics' });
        const seminars = [
            createSeminar({ name: 'Seminar Alpha' }),
            createSeminar({ name: 'Seminar Beta' }),
        ];
        vi.mocked(subjectsApi.get).mockResolvedValue({ data: subject });
        vi.mocked(seminarsApi.bySubject).mockResolvedValue({ data: seminars, meta: { current_page: 1, last_page: 1, per_page: 10, total: 2, from: 1, to: 2 }, links: { first: '', last: '', prev: null, next: null } });

        render(<SubjectSeminars />);

        await waitFor(() => {
            expect(screen.getByText('Seminar Alpha')).toBeInTheDocument();
            expect(screen.getByText('Seminar Beta')).toBeInTheDocument();
        });
    });

    it('shows empty state when no seminars', async () => {
        const subject = createSubject({ name: 'Empty Topic' });
        vi.mocked(subjectsApi.get).mockResolvedValue({ data: subject });
        vi.mocked(seminarsApi.bySubject).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 }, links: { first: '', last: '', prev: null, next: null } });

        render(<SubjectSeminars />);

        await waitFor(() => {
            expect(screen.getByText(/nenhum seminário encontrado/i)).toBeInTheDocument();
        });
    });
});
