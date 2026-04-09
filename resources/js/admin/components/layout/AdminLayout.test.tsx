import { render, screen } from '@/test/test-utils';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: { id: 1, name: 'Admin', email: 'admin@test.com', roles: ['admin'], is_admin: true },
        isLoading: false,
        isAuthenticated: true,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: vi.fn(),
    })),
}));

vi.mock('@shared/components/Favicon', () => ({
    Favicon: () => null,
}));

vi.mock('./MobileHeader', () => ({
    MobileHeader: () => <div data-testid="mobile-header">MobileHeader</div>,
}));

vi.mock('./Sidebar', () => ({
    Sidebar: () => <div data-testid="sidebar">Sidebar</div>,
}));

vi.mock('sonner', () => ({
    Toaster: () => null,
}));

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        Outlet: () => <div data-testid="outlet">Outlet Content</div>,
    };
});

import { AdminLayout } from './AdminLayout';

describe('AdminLayout', () => {
    it('renders the sidebar', () => {
        render(<AdminLayout />);
        expect(screen.getByTestId('sidebar')).toBeInTheDocument();
    });

    it('renders the mobile header', () => {
        render(<AdminLayout />);
        expect(screen.getByTestId('mobile-header')).toBeInTheDocument();
    });

    it('renders the outlet (children)', () => {
        render(<AdminLayout />);
        expect(screen.getByTestId('outlet')).toBeInTheDocument();
    });

    it('shows loading spinner when auth is loading', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: null,
            isLoading: true,
            isAuthenticated: false,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<AdminLayout />);
        expect(screen.queryByTestId('outlet')).not.toBeInTheDocument();
    });

    it('does not render content when user is not authenticated', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: null,
            isLoading: false,
            isAuthenticated: false,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<AdminLayout />);
        expect(screen.queryByTestId('outlet')).not.toBeInTheDocument();
    });

    it('does not render content when user lacks admin access', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 2, name: 'Regular', email: 'user@test.com', roles: ['user'] } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<AdminLayout />);
        expect(screen.queryByTestId('outlet')).not.toBeInTheDocument();
    });
});
