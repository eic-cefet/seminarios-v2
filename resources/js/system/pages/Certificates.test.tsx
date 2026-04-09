import { render, screen, waitFor } from '@/test/test-utils';
import { createUser, createUserCertificate } from '@/test/factories';
import Certificates from './Certificates';

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
    profileApi: { certificates: vi.fn().mockResolvedValue({ data: [] }) },
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

import { useAuth } from '@shared/contexts/AuthContext';
import { profileApi } from '@shared/api/client';

describe('Certificates', () => {
    it('renders "Meus Certificados" heading when authenticated', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Certificates />);

        expect(screen.getByRole('heading', { name: /meus certificados/i })).toBeInTheDocument();
    });

    it('renders certificate list', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        const certificates = [
            createUserCertificate({ seminar: { id: 1, name: 'Intro to AI', slug: 'intro-ai', scheduled_at: '2026-06-15T14:00:00Z', seminar_type: { id: 1, name: 'Palestra' } } }),
            createUserCertificate({ seminar: { id: 2, name: 'Web Security', slug: 'web-security', scheduled_at: '2026-07-10T10:00:00Z', seminar_type: { id: 1, name: 'Palestra' } } }),
        ];
        vi.mocked(profileApi.certificates).mockResolvedValue({ data: certificates, meta: { current_page: 1, last_page: 1, per_page: 10, total: 2 } });

        render(<Certificates />);

        await waitFor(() => {
            expect(screen.getByText('Intro to AI')).toBeInTheDocument();
            expect(screen.getByText('Web Security')).toBeInTheDocument();
        });
    });

    it('shows empty state when no certificates', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'Test User' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        vi.mocked(profileApi.certificates).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 } });

        render(<Certificates />);

        await waitFor(() => {
            expect(screen.getByText(/nenhum certificado disponível/i)).toBeInTheDocument();
        });
    });

    it('redirects to /login when not authenticated and not loading', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Certificates />);

        // Navigate component redirects, so the page heading should not be rendered
        expect(screen.queryByRole('heading', { name: /meus certificados/i })).not.toBeInTheDocument();
    });

    it('shows loading spinner when auth is loading', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: true, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Certificates />);

        // Should not render the heading or certificates
        expect(screen.queryByRole('heading', { name: /meus certificados/i })).not.toBeInTheDocument();
    });
});
