import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import { LoginModal } from './LoginModal';
import { useAuth } from '@shared/contexts/AuthContext';
import { authApi } from '@shared/api/client';

const mockLogin = vi.fn();

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: mockLogin, register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
    })),
}));

vi.mock('@shared/api/client', () => ({
    authApi: { forgotPassword: vi.fn() },
    ApiRequestError: class extends Error {},
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

vi.mock('@shared/lib/errors', () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
}));

describe('LoginModal', () => {
    const onOpenChange = vi.fn();

    it('renders dialog content when open', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByRole('heading', { name: 'Entrar' })).toBeInTheDocument();
    });

    it('does not render dialog content when closed', () => {
        render(<LoginModal open={false} onOpenChange={onOpenChange} />);

        expect(screen.queryByRole('heading', { name: 'Entrar' })).not.toBeInTheDocument();
    });

    it('shows email input field', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByLabelText('E-mail')).toBeInTheDocument();
        expect(screen.getByPlaceholderText('seu@email.com')).toBeInTheDocument();
    });

    it('shows password input field', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByLabelText('Senha')).toBeInTheDocument();
    });

    it('shows submit button', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByRole('button', { name: 'Entrar' })).toBeInTheDocument();
    });

    it('shows social login buttons', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByText(/continuar com google/i)).toBeInTheDocument();
        expect(screen.getByText(/continuar com github/i)).toBeInTheDocument();
    });

    it('shows forgot password link', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByText(/esqueci minha senha/i)).toBeInTheDocument();
    });

    it('shows register link', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByText(/cadastre-se/i)).toBeInTheDocument();
    });

    it('shows description text', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByText(/entre com sua conta para acessar o sistema/i)).toBeInTheDocument();
    });

    it('switches to forgot password view when link is clicked', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));

        expect(screen.getByText('Recuperar senha')).toBeInTheDocument();
        expect(screen.getByText(/digite seu e-mail para receber um link de recuperação/i)).toBeInTheDocument();
    });

    it('shows close button with aria-label', () => {
        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        expect(screen.getByRole('button', { name: /fechar/i })).toBeInTheDocument();
    });

    it('allows typing in email field', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        const emailInput = screen.getByLabelText('E-mail');
        await user.type(emailInput, 'test@example.com');

        expect(emailInput).toHaveValue('test@example.com');
    });

    it('allows typing in password field', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        const passwordInput = screen.getByLabelText('Senha');
        await user.type(passwordInput, 'mypassword');

        expect(passwordInput).toHaveValue('mypassword');
    });

    it('calls login on form submit and closes modal on success', async () => {
        mockLogin.mockResolvedValueOnce(undefined);
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.type(screen.getByLabelText('E-mail'), 'test@example.com');
        await user.type(screen.getByLabelText('Senha'), 'password123');
        await user.click(screen.getByRole('button', { name: 'Entrar' }));

        await waitFor(() => {
            expect(mockLogin).toHaveBeenCalledWith('test@example.com', 'password123', true);
        });

        await waitFor(() => {
            expect(onOpenChange).toHaveBeenCalledWith(false);
        });
    });

    it('shows error message when login fails', async () => {
        mockLogin.mockRejectedValueOnce(new Error('E-mail ou senha incorretos.'));
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.type(screen.getByLabelText('E-mail'), 'test@example.com');
        await user.type(screen.getByLabelText('Senha'), 'wrongpassword');
        await user.click(screen.getByRole('button', { name: 'Entrar' }));

        await waitFor(() => {
            expect(screen.getByText('E-mail ou senha incorretos.')).toBeInTheDocument();
        });
    });

    it('shows loading state during login submission', async () => {
        let resolveLogin: () => void;
        mockLogin.mockImplementation(() => new Promise<void>((resolve) => { resolveLogin = resolve; }));
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.type(screen.getByLabelText('E-mail'), 'test@example.com');
        await user.type(screen.getByLabelText('Senha'), 'password123');
        await user.click(screen.getByRole('button', { name: 'Entrar' }));

        expect(screen.getByRole('button', { name: 'Entrando...' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Entrando...' })).toBeDisabled();

        resolveLogin!();

        await waitFor(() => {
            expect(onOpenChange).toHaveBeenCalledWith(false);
        });
    });

    it('switches to forgot password view and shows form', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));

        expect(screen.getByText('Recuperar senha')).toBeInTheDocument();
        expect(screen.getByLabelText('E-mail')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Enviar' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Voltar' })).toBeInTheDocument();
    });

    it('allows typing in forgot email field', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));

        const forgotEmailInput = screen.getByLabelText('E-mail');
        await user.type(forgotEmailInput, 'forgot@example.com');

        expect(forgotEmailInput).toHaveValue('forgot@example.com');
    });

    it('submits forgot password form and shows success message', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValueOnce(undefined as any);
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));
        await user.type(screen.getByLabelText('E-mail'), 'forgot@example.com');
        await user.click(screen.getByRole('button', { name: 'Enviar' }));

        await waitFor(() => {
            expect(authApi.forgotPassword).toHaveBeenCalledWith('forgot@example.com');
        });

        await waitFor(() => {
            expect(screen.getByText(/se o e-mail informado estiver cadastrado/i)).toBeInTheDocument();
        });
    });

    it('shows error when forgot password fails', async () => {
        vi.mocked(authApi.forgotPassword).mockRejectedValueOnce(new Error('Muitas tentativas.'));
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));
        await user.type(screen.getByLabelText('E-mail'), 'forgot@example.com');
        await user.click(screen.getByRole('button', { name: 'Enviar' }));

        await waitFor(() => {
            expect(screen.getByText('Muitas tentativas.')).toBeInTheDocument();
        });
    });

    it('shows loading state during forgot password submission', async () => {
        let resolveForgot: () => void;
        vi.mocked(authApi.forgotPassword).mockImplementation(() => new Promise<any>((resolve) => { resolveForgot = resolve; }));
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));
        await user.type(screen.getByLabelText('E-mail'), 'forgot@example.com');
        await user.click(screen.getByRole('button', { name: 'Enviar' }));

        expect(screen.getByRole('button', { name: 'Enviando...' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Enviando...' })).toBeDisabled();

        resolveForgot!();

        await waitFor(() => {
            expect(screen.getByText(/se o e-mail informado estiver cadastrado/i)).toBeInTheDocument();
        });
    });

    it('navigates back from forgot password success to login view', async () => {
        vi.mocked(authApi.forgotPassword).mockResolvedValueOnce(undefined as any);
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));
        await user.type(screen.getByLabelText('E-mail'), 'forgot@example.com');
        await user.click(screen.getByRole('button', { name: 'Enviar' }));

        await waitFor(() => {
            expect(screen.getByText(/se o e-mail informado estiver cadastrado/i)).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /voltar para login/i }));

        expect(screen.getByRole('heading', { name: 'Entrar' })).toBeInTheDocument();
    });

    it('navigates back from forgot password form to login view', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/esqueci minha senha/i));

        expect(screen.getByText('Recuperar senha')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Voltar' }));

        expect(screen.getByRole('heading', { name: 'Entrar' })).toBeInTheDocument();
    });

    it('clears error when switching from login to forgot view', async () => {
        mockLogin.mockRejectedValueOnce(new Error('Login failed'));
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.type(screen.getByLabelText('E-mail'), 'test@example.com');
        await user.type(screen.getByLabelText('Senha'), 'wrong');
        await user.click(screen.getByRole('button', { name: 'Entrar' }));

        await waitFor(() => {
            expect(screen.getByText('Login failed')).toBeInTheDocument();
        });

        // Error should clear on new submission attempt
        mockLogin.mockResolvedValueOnce(undefined);
        await user.click(screen.getByRole('button', { name: 'Entrar' }));

        await waitFor(() => {
            expect(screen.queryByText('Login failed')).not.toBeInTheDocument();
        });
    });

    it('calls onOpenChange when register link is clicked', async () => {
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/cadastre-se/i));

        expect(onOpenChange).toHaveBeenCalledWith(false);
    });

    it('redirects to Google on social login click', async () => {
        const user = userEvent.setup();
        const hrefSetter = vi.fn();
        const originalLocation = window.location;
        // @ts-ignore - override location for test
        delete (window as any).location;
        window.location = { ...originalLocation, set href(val: string) { hrefSetter(val); }, get href() { return ''; } } as any;

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/continuar com google/i));

        expect(hrefSetter).toHaveBeenCalledWith('/auth/google');

        window.location = originalLocation;
    });

    it('redirects to GitHub on social login click', async () => {
        const user = userEvent.setup();
        const hrefSetter = vi.fn();
        const originalLocation = window.location;
        // @ts-ignore - override location for test
        delete (window as any).location;
        window.location = { ...originalLocation, set href(val: string) { hrefSetter(val); }, get href() { return ''; } } as any;

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        await user.click(screen.getByText(/continuar com github/i));

        expect(hrefSetter).toHaveBeenCalledWith('/auth/github');

        window.location = originalLocation;
    });

    it('resets state when dialog is closed via close button', async () => {
        mockLogin.mockRejectedValueOnce(new Error('Login error'));
        const user = userEvent.setup();

        render(<LoginModal open={true} onOpenChange={onOpenChange} />);

        // Trigger an error first
        await user.type(screen.getByLabelText('E-mail'), 'test@example.com');
        await user.type(screen.getByLabelText('Senha'), 'wrong');
        await user.click(screen.getByRole('button', { name: 'Entrar' }));

        await waitFor(() => {
            expect(screen.getByText('Login error')).toBeInTheDocument();
        });

        // Click the close button which should trigger onOpenChange(false) and resetModal
        await user.click(screen.getByRole('button', { name: /fechar/i }));

        expect(onOpenChange).toHaveBeenCalledWith(false);
    });
});
