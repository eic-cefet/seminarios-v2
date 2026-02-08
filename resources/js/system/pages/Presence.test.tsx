import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import { createUser } from '@/test/factories';
import Presence from './Presence';

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
    ApiRequestError: class extends Error {},
}));

vi.mock('@shared/api/httpUtils', () => ({
    getCookie: vi.fn(() => null),
    getCsrfCookie: vi.fn().mockResolvedValue(undefined),
}));

vi.mock('../components/LoginModal', () => ({
    LoginModal: ({ open }: { open: boolean }) => open ? <div data-testid="login-modal">Login Modal</div> : null,
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useParams: vi.fn(() => ({ uuid: 'test-uuid-123' })), useNavigate: vi.fn(() => vi.fn()) };
});

import { useAuth } from '@shared/contexts/AuthContext';

const validPresenceResponse = () =>
    new Response(JSON.stringify({
        data: {
            seminar: { id: 1, name: 'Test Seminar', scheduled_at: '2026-06-15T14:00:00Z' },
            is_valid: true,
            expires_at: '2026-06-15T16:00:00Z',
        },
    }), { status: 200, headers: { 'Content-Type': 'application/json' } });

const successRegisterResponse = () =>
    new Response(JSON.stringify({ message: 'Presença registrada com sucesso' }), {
        status: 200, headers: { 'Content-Type': 'application/json' },
    });

describe('Presence', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.spyOn(globalThis, 'fetch').mockResolvedValue(validPresenceResponse());
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('shows loading state initially', () => {
        render(<Presence />);
        expect(screen.getByText(/verificando link/i)).toBeInTheDocument();
    });

    it('shows invalid link state when fetch fails', async () => {
        vi.spyOn(globalThis, 'fetch').mockResolvedValue(
            new Response(JSON.stringify({ message: 'Link expirado' }), { status: 404, headers: { 'Content-Type': 'application/json' } }),
        );

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByText(/link inválido/i)).toBeInTheDocument();
        });
    });

    it('shows error message from server in invalid link state', async () => {
        vi.spyOn(globalThis, 'fetch').mockResolvedValue(
            new Response(JSON.stringify({ message: 'Este link expirou' }), { status: 400, headers: { 'Content-Type': 'application/json' } }),
        );

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByText('Este link expirou')).toBeInTheDocument();
        });
    });

    it('shows "Voltar para o inicio" link in invalid state', async () => {
        vi.spyOn(globalThis, 'fetch').mockResolvedValue(
            new Response(JSON.stringify({ message: 'Invalid' }), { status: 404, headers: { 'Content-Type': 'application/json' } }),
        );

        render(<Presence />);

        await waitFor(() => {
            const link = screen.getByRole('link', { name: /voltar para o início/i });
            expect(link).toHaveAttribute('href', '/');
        });
    });

    it('shows unauthenticated state with login prompt when not logged in and link is valid', async () => {
        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /registrar presença/i })).toBeInTheDocument();
        });

        expect(screen.getByText(/você precisa estar autenticado/i)).toBeInTheDocument();
    });

    it('shows seminar info in unauthenticated state', async () => {
        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByText('Test Seminar')).toBeInTheDocument();
        });
    });

    it('shows "Entrar na conta" button when not authenticated', async () => {
        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /entrar na conta/i })).toBeInTheDocument();
        });
    });

    it('shows "Criar conta" link when not authenticated', async () => {
        render(<Presence />);

        await waitFor(() => {
            const links = screen.getAllByRole('link', { name: /criar conta/i });
            const cadastroLink = links.find((l) => l.getAttribute('href') === '/cadastro');
            expect(cadastroLink).toBeDefined();
        });
    });

    it('opens login modal when "Entrar na conta" is clicked', async () => {
        const user = userEvent.setup();
        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /entrar na conta/i })).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /entrar na conta/i }));

        expect(screen.getByTestId('login-modal')).toBeInTheDocument();
    });

    it('auto-registers presence when user is authenticated and link is valid', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse()) // check link
            .mockResolvedValueOnce(successRegisterResponse()); // register

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /presença registrada/i })).toBeInTheDocument();
        });

        expect(screen.getAllByText('John Doe').length).toBeGreaterThan(0);
    });

    it('shows success state with seminar details after registration', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(successRegisterResponse());

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByText('Test Seminar')).toBeInTheDocument();
            expect(screen.getByRole('heading', { name: /presença registrada/i })).toBeInTheDocument();
        });
    });

    it('shows links to profile and home in success state', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(successRegisterResponse());

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('link', { name: /ver minhas inscrições/i })).toHaveAttribute('href', '/perfil');
        });

        expect(screen.getByRole('link', { name: /voltar para o início/i })).toHaveAttribute('href', '/');
    });

    it('shows error state when registration fails', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(new Response(
                JSON.stringify({ message: 'Já registrado' }),
                { status: 409, headers: { 'Content-Type': 'application/json' } },
            ));

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /erro ao registrar/i })).toBeInTheDocument();
        });
    });

    it('shows error message from server in error state', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(new Response(
                JSON.stringify({ message: 'Você já está inscrito' }),
                { status: 409, headers: { 'Content-Type': 'application/json' } },
            ));

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByText('Você já está inscrito')).toBeInTheDocument();
        });
    });

    it('shows retry button in error state', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(new Response(
                JSON.stringify({ message: 'Server error' }),
                { status: 500, headers: { 'Content-Type': 'application/json' } },
            ));

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /tentar novamente/i })).toBeInTheDocument();
        });
    });

    it('shows home link in error state', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(new Response(
                JSON.stringify({ message: 'Server error' }),
                { status: 500, headers: { 'Content-Type': 'application/json' } },
            ));

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('link', { name: /voltar para o início/i })).toHaveAttribute('href', '/');
        });
    });

    it('shows fallback error message when no specific message in server response', async () => {
        vi.spyOn(globalThis, 'fetch').mockResolvedValue(
            new Response(JSON.stringify({}), { status: 404, headers: { 'Content-Type': 'application/json' } }),
        );

        render(<Presence />);

        await waitFor(() => {
            // The fetch handler uses: throw new Error(error.message || "Link inválido")
            // Since no message is in response, it falls back to "Link inválido"
            expect(screen.getByText('Link inválido')).toBeInTheDocument();
        });
    });

    it('shows fallback message when link data has is_valid=false', async () => {
        vi.spyOn(globalThis, 'fetch').mockResolvedValue(
            new Response(JSON.stringify({
                data: {
                    seminar: { id: 1, name: 'Test', scheduled_at: '2026-06-15T14:00:00Z' },
                    is_valid: false,
                    expires_at: '2026-06-15T16:00:00Z',
                },
            }), { status: 200, headers: { 'Content-Type': 'application/json' } }),
        );

        render(<Presence />);

        await waitFor(() => {
            // When is_valid is false, component shows the default invalid message
            expect(screen.getByText(/este link de presença não é válido ou expirou/i)).toBeInTheDocument();
        });
    });

    it('includes XSRF-TOKEN header when cookie is present for link check', async () => {
        const { getCookie } = await import('@shared/api/httpUtils');
        vi.mocked(getCookie).mockReturnValue('xsrf-token-value');

        const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValue(validPresenceResponse());

        render(<Presence />);

        await waitFor(() => {
            expect(fetchSpy).toHaveBeenCalledWith(
                expect.stringContaining('/presence/'),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'X-XSRF-TOKEN': 'xsrf-token-value',
                    }),
                }),
            );
        });
    });

    it('includes XSRF-TOKEN header when cookie is present for registration', async () => {
        const { getCookie } = await import('@shared/api/httpUtils');
        vi.mocked(getCookie).mockReturnValue('xsrf-token-value');

        const fetchSpy = vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(successRegisterResponse());

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            // Should have been called for registration with the XSRF token
            const registerCall = fetchSpy.mock.calls.find(call =>
                typeof call[0] === 'string' && call[0].includes('/register'),
            );
            expect(registerCall).toBeDefined();
            expect((registerCall![1] as any).headers['X-XSRF-TOKEN']).toBe('xsrf-token-value');
        });
    });

    it('retries registration when "Tentar novamente" button is clicked', async () => {
        vi.spyOn(globalThis, 'fetch')
            .mockResolvedValueOnce(validPresenceResponse())
            .mockResolvedValueOnce(new Response(
                JSON.stringify({ message: 'Server error' }),
                { status: 500, headers: { 'Content-Type': 'application/json' } },
            ))
            .mockResolvedValueOnce(successRegisterResponse()); // retry succeeds

        vi.mocked(useAuth).mockReturnValue({
            user: createUser({ name: 'John Doe' }), isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Presence />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /tentar novamente/i })).toBeInTheDocument();
        });

        const user = userEvent.setup();
        await user.click(screen.getByRole('button', { name: /tentar novamente/i }));

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: /presença registrada/i })).toBeInTheDocument();
        });
    });
});
