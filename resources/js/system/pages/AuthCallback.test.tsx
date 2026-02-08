import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor, userEvent } from '@/test/test-utils';
import AuthCallback from './AuthCallback';

const mockExchangeCode = vi.fn();
const mockNavigate = vi.fn();

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: mockExchangeCode,
        refreshUser: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return { ...actual, useNavigate: () => mockNavigate };
});

import { useAuth } from '@shared/contexts/AuthContext';

describe('AuthCallback', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockExchangeCode.mockResolvedValue(undefined);
    });

    it('shows loading state "Autenticando..." when code is present', () => {
        render(<AuthCallback />, {
            routerProps: { initialEntries: ['/auth/callback?code=abc'] },
        });

        expect(screen.getByText(/autenticando.../i)).toBeInTheDocument();
    });

    it('shows error when error param is present', () => {
        render(<AuthCallback />, {
            routerProps: { initialEntries: ['/auth/callback?error=access_denied'] },
        });

        expect(screen.getByText(/erro na autenticação/i)).toBeInTheDocument();
    });

    it('shows error when no code param is present', () => {
        render(<AuthCallback />, {
            routerProps: { initialEntries: ['/auth/callback'] },
        });

        expect(screen.getByText(/código de autenticação não encontrado/i)).toBeInTheDocument();
    });

    it('navigates to "/" when exchange completes and user is set', async () => {
        mockExchangeCode.mockResolvedValue(undefined);

        // First render without user
        const { rerender } = render(<AuthCallback />, {
            routerProps: { initialEntries: ['/auth/callback?code=valid-code'] },
        });

        // Wait for exchange to complete, then simulate user being set
        await waitFor(() => {
            expect(mockExchangeCode).toHaveBeenCalledWith('valid-code');
        });

        // Now mock useAuth to return a user (simulating post-exchange state)
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Test User', email: 'test@test.com' },
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: mockExchangeCode,
            refreshUser: vi.fn(),
        });

        // Force re-render with updated auth state
        rerender(<AuthCallback />);

        await waitFor(() => {
            expect(mockNavigate).toHaveBeenCalledWith('/', { replace: true });
        });
    });

    it('shows error when exchangeCode fails', async () => {
        mockExchangeCode.mockRejectedValue(new Error('Exchange failed'));

        render(<AuthCallback />, {
            routerProps: { initialEntries: ['/auth/callback?code=bad-code'] },
        });

        await waitFor(() => {
            expect(screen.getByText('Exchange failed')).toBeInTheDocument();
        });
    });

    it('navigates to /login when "Voltar para login" button is clicked', async () => {
        render(<AuthCallback />, {
            routerProps: { initialEntries: ['/auth/callback?error=access_denied'] },
        });

        const user = userEvent.setup();
        const button = screen.getByRole('button', { name: /voltar para login/i });
        await user.click(button);

        expect(mockNavigate).toHaveBeenCalledWith('/login');
    });
});
