import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import ForgotPassword from './ForgotPassword';

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
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {
        code: string; status: number;
        constructor(code: string, msg: string, status: number) { super(msg); this.code = code; this.status = status; }
    },
}));

import { authApi } from '@shared/api/client';
import { analytics } from '@shared/lib/analytics';

describe('ForgotPassword', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders "Recuperar senha" heading', () => {
        render(<ForgotPassword />);
        expect(screen.getByRole('heading', { name: /recuperar senha/i })).toBeInTheDocument();
    });

    it('renders email input', () => {
        render(<ForgotPassword />);
        expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument();
    });

    it('renders link back to login', () => {
        render(<ForgotPassword />);
        const link = screen.getByRole('link', { name: /voltar para o login/i });
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/login');
    });

    it('renders submit button with correct text', () => {
        render(<ForgotPassword />);
        expect(screen.getByRole('button', { name: /enviar link de recuperação/i })).toBeInTheDocument();
    });

    it('renders description text', () => {
        render(<ForgotPassword />);
        expect(screen.getByText(/digite seu e-mail e enviaremos um link para/i)).toBeInTheDocument();
    });

    it('allows typing in the email field', async () => {
        const user = userEvent.setup();
        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        expect(screen.getByLabelText(/e-mail/i)).toHaveValue('test@example.com');
    });

    it('shows success state after successful submission', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValue({ message: 'ok' });
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /e-mail enviado/i })).toBeInTheDocument();
        });
    });

    it('displays the entered email in success state', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValue({ message: 'ok' });
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'user@test.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByText('user@test.com')).toBeInTheDocument();
        });
    });

    it('shows spam folder message in success state', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValue({ message: 'ok' });
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByText(/verifique sua caixa de entrada e a pasta de spam/i)).toBeInTheDocument();
        });
    });

    it('shows link back to login in success state', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValue({ message: 'ok' });
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /voltar para o login/i });
            expect(link).toHaveAttribute('href', '/login');
        });
    });

    it('fires analytics event on successful submission', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValue({ message: 'ok' });
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(analytics.event).toHaveBeenCalledWith('forgot_password_submit');
        });
    });

    it('shows error message when API call fails', async () => {
        vi.mocked(authApi.forgotPassword).mockRejectedValue(new Error('Network error'));
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByText('Network error')).toBeInTheDocument();
        });
    });

    it('shows loading state while submitting', async () => {
        vi.mocked(authApi.forgotPassword).mockImplementation(() => new Promise(() => {}));
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /enviando/i })).toBeInTheDocument();
        });
    });

    it('disables button while loading', async () => {
        vi.mocked(authApi.forgotPassword).mockImplementation(() => new Promise(() => {}));
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /enviando/i })).toBeDisabled();
        });
    });

    it('clears previous error before new submission', async () => {
        vi.mocked(authApi.forgotPassword)
            .mockRejectedValueOnce(new Error('First error'))
            .mockResolvedValueOnce({ message: 'ok' });
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByText('First error')).toBeInTheDocument();
        });

        // Submit again
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.queryByText('First error')).not.toBeInTheDocument();
        });
    });

    it('re-enables the button after an error', async () => {
        vi.mocked(authApi.forgotPassword).mockRejectedValue(new Error('Some error'));
        const user = userEvent.setup();

        render(<ForgotPassword />);

        await user.type(screen.getByLabelText(/e-mail/i), 'test@example.com');
        await user.click(screen.getByRole('button', { name: /enviar link de recuperação/i }));

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /enviar link de recuperação/i })).not.toBeDisabled();
        });
    });
});
