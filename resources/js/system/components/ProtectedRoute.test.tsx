import { render, screen } from '@/test/test-utils';
import { Route, Routes, useLocation } from 'react-router-dom';
import { ProtectedRoute } from './ProtectedRoute';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

import { useAuth } from '@shared/contexts/AuthContext';

function TestApp() {
    return (
        <Routes>
            <Route
                path="/login"
                element={<div data-testid="login-page">Login Page</div>}
            />
            <Route
                path="/perfil"
                element={
                    <ProtectedRoute>
                        <div data-testid="protected-content">Protected</div>
                    </ProtectedRoute>
                }
            />
        </Routes>
    );
}

function LoginWithState() {
    const location = useLocation();
    return (
        <div data-testid="login-page">
            <span data-testid="from-state">{(location.state as { from?: string })?.from ?? 'none'}</span>
        </div>
    );
}

function AppWithLoginState() {
    return (
        <Routes>
            <Route path="/login" element={<LoginWithState />} />
            <Route
                path="/perfil"
                element={
                    <ProtectedRoute>
                        <div>Protected</div>
                    </ProtectedRoute>
                }
            />
        </Routes>
    );
}

describe('ProtectedRoute', () => {
    it('renders children when user is authenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Test', email: 'test@test.com' },
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<TestApp />, {
            routerProps: { initialEntries: ['/perfil'] },
        });

        expect(screen.getByTestId('protected-content')).toBeInTheDocument();
    });

    it('redirects to /login when user is not authenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<TestApp />, {
            routerProps: { initialEntries: ['/perfil'] },
        });

        expect(screen.getByTestId('login-page')).toBeInTheDocument();
    });

    it('shows loading spinner when auth is loading', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: true, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<TestApp />, {
            routerProps: { initialEntries: ['/perfil'] },
        });

        expect(screen.queryByTestId('protected-content')).not.toBeInTheDocument();
        expect(screen.queryByTestId('login-page')).not.toBeInTheDocument();
    });

    it('passes the current path as state.from when redirecting to login', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<AppWithLoginState />, {
            routerProps: { initialEntries: ['/perfil'] },
        });

        expect(screen.getByTestId('from-state')).toHaveTextContent('/perfil');
    });

    it('includes search params in the from state', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: vi.fn(), exchangeCode: vi.fn(), refreshUser: vi.fn(), completeTwoFactor: vi.fn(),
        });

        render(<AppWithLoginState />, {
            routerProps: { initialEntries: ['/perfil?tab=certificates'] },
        });

        expect(screen.getByTestId('from-state')).toHaveTextContent('/perfil?tab=certificates');
    });
});
