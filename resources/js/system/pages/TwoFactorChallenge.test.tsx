import { render, screen, userEvent, waitFor } from '@/test/test-utils';
import TwoFactorChallenge from './TwoFactorChallenge';

const mockNavigate = vi.fn();
const mockCompleteTwoFactor = vi.fn();

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(),
        refreshUser: vi.fn(), completeTwoFactor: mockCompleteTwoFactor,
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@shared/api/twoFactorApi', () => ({
    twoFactorApi: { challenge: vi.fn() },
}));

vi.mock('@shared/lib/errors', () => ({
    getErrorMessage: vi.fn((err: Error) => err.message),
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useNavigate: () => mockNavigate };
});

import { twoFactorApi } from '@shared/api/twoFactorApi';

describe('TwoFactorChallenge', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    function renderPage(state: unknown = { challengeToken: 'abc' }) {
        return render(<TwoFactorChallenge />, {
            routerProps: {
                initialEntries: [{ pathname: '/login/two-factor', state }],
            },
        });
    }

    it('redirects to /login when no challenge token is in state', async () => {
        renderPage({});
        await waitFor(() => {
            expect(mockNavigate).toHaveBeenCalledWith('/login', { replace: true });
        });
    });

    it('submits the TOTP code and navigates home on success', async () => {
        vi.mocked(twoFactorApi.challenge).mockResolvedValueOnce({ user: { id: 1 } } as never);
        const user = userEvent.setup();
        renderPage({ challengeToken: 'abc', remember: false, from: '/' });

        await user.type(screen.getByLabelText(/^código$/i), '123456');
        await user.click(screen.getByRole('button', { name: /verificar/i }));

        await waitFor(() => {
            expect(twoFactorApi.challenge).toHaveBeenCalledWith({
                challenge_token: 'abc',
                code: '123456',
                remember_device: false,
            });
            expect(mockCompleteTwoFactor).toHaveBeenCalled();
            expect(mockNavigate).toHaveBeenCalledWith('/', { replace: true });
        });
    });

    it('switches to recovery code mode and submits a recovery code', async () => {
        vi.mocked(twoFactorApi.challenge).mockResolvedValueOnce({ user: { id: 1 } } as never);
        const user = userEvent.setup();
        renderPage();

        await user.click(screen.getByRole('button', { name: /usar código de recuperação/i }));

        await user.type(screen.getByLabelText(/código de recuperação/i), 'recovery-1');
        await user.click(screen.getByRole('button', { name: /verificar/i }));

        await waitFor(() => {
            expect(twoFactorApi.challenge).toHaveBeenCalledWith({
                challenge_token: 'abc',
                recovery_code: 'recovery-1',
                remember_device: false,
            });
        });
    });

    it('passes remember_device when checkbox is checked', async () => {
        vi.mocked(twoFactorApi.challenge).mockResolvedValueOnce({ user: { id: 1 } } as never);
        const user = userEvent.setup();
        renderPage();

        await user.type(screen.getByLabelText(/^código$/i), '123456');
        await user.click(screen.getByRole('checkbox', { name: /lembrar este dispositivo/i }));
        await user.click(screen.getByRole('button', { name: /verificar/i }));

        await waitFor(() => {
            expect(twoFactorApi.challenge).toHaveBeenCalledWith(
                expect.objectContaining({ remember_device: true }),
            );
        });
    });

    it('navigates back to /login when the Voltar button is clicked', async () => {
        const user = userEvent.setup();
        renderPage();

        await user.click(screen.getByRole('button', { name: /voltar/i }));

        expect(mockNavigate).toHaveBeenCalledWith('/login', { replace: true });
    });

    it('shows an error when the API call fails', async () => {
        vi.mocked(twoFactorApi.challenge).mockRejectedValueOnce(new Error('Código inválido'));
        const user = userEvent.setup();
        renderPage();

        await user.type(screen.getByLabelText(/^código$/i), '000000');
        await user.click(screen.getByRole('button', { name: /verificar/i }));

        await waitFor(() => {
            expect(screen.getByRole('alert')).toHaveTextContent('Código inválido');
        });
        expect(mockCompleteTwoFactor).not.toHaveBeenCalled();
    });
});
