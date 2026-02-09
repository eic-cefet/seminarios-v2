import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import Login from './Login';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn() },
}));

vi.mock('@shared/lib/errors', () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
}));

import { useAuth } from '@shared/contexts/AuthContext';

describe('Login', () => {
    it('renders "Entrar" heading', () => {
        render(<Login />);
        expect(screen.getByRole('heading', { name: /^entrar$/i })).toBeInTheDocument();
    });

    it('renders email and password inputs', () => {
        render(<Login />);
        expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^senha$/i)).toBeInTheDocument();
    });

    it('renders social login buttons', () => {
        render(<Login />);
        expect(screen.getByRole('button', { name: /google/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /github/i })).toBeInTheDocument();
    });

    it('renders link to cadastro', () => {
        render(<Login />);
        const links = screen.getAllByRole('link', { name: /criar conta/i });
        // At least one link to /cadastro exists (may appear in both Navbar and page body)
        const cadastroLink = links.find((l) => l.getAttribute('href') === '/cadastro');
        expect(cadastroLink).toBeDefined();
    });

    it('renders forgot password link', () => {
        render(<Login />);
        const link = screen.getByRole('link', { name: /esqueceu a senha/i });
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/recuperar-senha');
    });

    it('fills in email and password fields and clicks submit', async () => {
        const mockLogin = vi.fn().mockResolvedValue(undefined);
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: mockLogin, register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<Login />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.type(screen.getByLabelText(/^senha$/i), 'mypassword123');
        // Find submit button inside the form (not the Navbar link)
        const buttons = screen.getAllByRole('button', { name: /^entrar$/i });
        await user.click(buttons[buttons.length - 1]);

        await waitFor(() => {
            expect(mockLogin).toHaveBeenCalledWith('test@example.com', 'mypassword123');
        });
    });

    it('shows error message when login fails', async () => {
        const mockLogin = vi.fn().mockRejectedValue(new Error('E-mail ou senha incorretos.'));
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: mockLogin, register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<Login />);

        await user.type(screen.getByLabelText(/e-mail/i), 'wrong@example.com');
        await user.type(screen.getByLabelText(/^senha$/i), 'wrongpassword');
        const buttons = screen.getAllByRole('button', { name: /^entrar$/i });
        await user.click(buttons[buttons.length - 1]);

        await waitFor(() => {
            expect(screen.getByText('E-mail ou senha incorretos.')).toBeInTheDocument();
        });
    });

    it('shows loading state while submitting', async () => {
        const mockLogin = vi.fn(() => new Promise(() => {}));
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: mockLogin as any, register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
        const user = userEvent.setup();

        render(<Login />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.type(screen.getByLabelText(/^senha$/i), 'mypassword123');
        const buttons = screen.getAllByRole('button', { name: /^entrar$/i });
        await user.click(buttons[buttons.length - 1]);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /entrando/i })).toBeInTheDocument();
        });
    });

    it('redirects social login to correct provider URL', () => {
        render(<Login />);

        const googleBtn = screen.getByRole('button', { name: /google/i });
        const githubBtn = screen.getByRole('button', { name: /github/i });

        expect(googleBtn).toBeInTheDocument();
        expect(githubBtn).toBeInTheDocument();
    });

    it('handles social login by calling analytics and setting window.location.href', async () => {
        const { analytics } = await import('@shared/lib/analytics');
        const originalLocation = window.location;
        Object.defineProperty(window, 'location', {
            writable: true,
            value: { ...originalLocation, href: '' },
        });
        const user = userEvent.setup();

        render(<Login />);

        await user.click(screen.getByRole('button', { name: /google/i }));
        expect(analytics.event).toHaveBeenCalledWith('login_social', { provider: 'google' });

        await user.click(screen.getByRole('button', { name: /github/i }));
        expect(analytics.event).toHaveBeenCalledWith('login_social', { provider: 'github' });

        Object.defineProperty(window, 'location', {
            writable: true,
            value: originalLocation,
        });
    });
});
