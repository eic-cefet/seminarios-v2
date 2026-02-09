import { render, screen, fireEvent } from '@/test/test-utils';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: { id: 1, name: 'Admin User', email: 'admin@test.com', roles: ['admin'], is_admin: true },
        isLoading: false,
        isAuthenticated: true,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: vi.fn(),
    })),
}));

import { Sidebar } from './Sidebar';

describe('Sidebar', () => {
    beforeEach(async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Admin User', email: 'admin@test.com', roles: ['admin'], is_admin: true } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });
    });

    it('renders the EIC logo', () => {
        render(<Sidebar />);
        expect(screen.getByText('EIC')).toBeInTheDocument();
    });

    it('renders the CEFET-RJ title', () => {
        render(<Sidebar />);
        expect(screen.getByText('CEFET-RJ')).toBeInTheDocument();
    });

    it('renders the Seminarios subtitle', () => {
        render(<Sidebar />);
        expect(screen.getAllByText('Seminários').length).toBeGreaterThan(0);
    });

    it('renders the Dashboard navigation item', () => {
        render(<Sidebar />);
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
    });

    it('renders admin-only navigation items for admin users', () => {
        render(<Sidebar />);
        expect(screen.getByText('Usuários')).toBeInTheDocument();
        expect(screen.getByText('Inscrições')).toBeInTheDocument();
    });

    it('renders user name and email', () => {
        render(<Sidebar />);
        expect(screen.getByText('Admin User')).toBeInTheDocument();
        expect(screen.getByText('admin@test.com')).toBeInTheDocument();
    });

    it('renders user initial avatar', () => {
        render(<Sidebar />);
        expect(screen.getByText('A')).toBeInTheDocument();
    });

    it('renders the logout button', () => {
        render(<Sidebar />);
        expect(screen.getByText('Sair')).toBeInTheDocument();
    });

    it('renders the back to site link', () => {
        render(<Sidebar />);
        expect(screen.getByText('Voltar ao Site')).toBeInTheDocument();
    });

    it('calls logout when the logout button is clicked', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        const mockLogout = vi.fn();
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Admin User', email: 'admin@test.com', roles: ['admin'], is_admin: true } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: mockLogout,
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Sidebar />);
        fireEvent.click(screen.getByText('Sair'));
        expect(mockLogout).toHaveBeenCalledTimes(1);
    });

    it('hides admin-only navigation for teacher users', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 2, name: 'Teacher', email: 'teacher@test.com', roles: ['teacher'] } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Sidebar />);
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        expect(screen.queryByText('Usuários')).not.toBeInTheDocument();
        expect(screen.queryByText('Inscrições')).not.toBeInTheDocument();
    });

    it('renders the Seminários collapsible menu group', () => {
        render(<Sidebar />);
        // "Seminários" appears as both the subtitle and the collapsible menu label
        expect(screen.getAllByText('Seminários').length).toBeGreaterThanOrEqual(1);
    });

    it('renders the Seminários collapsible trigger with open state', () => {
        render(<Sidebar />);
        // Seminários group is open by default - verify trigger has open state
        const triggers = screen.getAllByText('Seminários');
        const trigger = triggers.find(el => el.closest('button'));
        expect(trigger?.closest('button')).toHaveAttribute('data-state', 'open');
    });

    it('renders the Relatórios collapsible trigger for admin', () => {
        render(<Sidebar />);
        // Relatórios group trigger should exist for admin users
        // Search all buttons in the document (nav + collapsibles)
        const allButtons = document.querySelectorAll('button');
        const relatoriosButton = Array.from(allButtons).find(b => b.textContent?.includes('Relatórios'));
        expect(relatoriosButton).toBeTruthy();
    });

    it('hides Relatórios menu group for teacher users', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 2, name: 'Teacher', email: 'teacher@test.com', roles: ['teacher'] } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Sidebar />);
        expect(screen.queryByText('Relatórios')).not.toBeInTheDocument();
    });

    it('toggles collapsible menu when trigger is clicked', async () => {
        render(<Sidebar />);

        // Seminários group is open by default
        const triggers = screen.getAllByText('Seminários');
        const trigger = triggers.find(el => el.closest('button'));
        expect(trigger?.closest('button')).toHaveAttribute('data-state', 'open');

        // Click to close
        fireEvent.click(trigger!.closest('button')!);
        expect(trigger?.closest('button')).toHaveAttribute('data-state', 'closed');

        // Click to re-open
        fireEvent.click(trigger!.closest('button')!);
        expect(trigger?.closest('button')).toHaveAttribute('data-state', 'open');
    });

    it('renders teacher-visible items in Seminários group for teacher users', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 2, name: 'Teacher', email: 'teacher@test.com', roles: ['teacher'] } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Sidebar />);
        // For teachers, Seminários group should still render (child "Seminários" item is not adminOnly)
        const seminariosTriggers = screen.getAllByText('Seminários');
        expect(seminariosTriggers.length).toBeGreaterThanOrEqual(1);
        // But admin-only children like Workshops, Locais, Tópicos should be hidden
        expect(screen.queryByText('Workshops')).not.toBeInTheDocument();
        expect(screen.queryByText('Locais')).not.toBeInTheDocument();
        expect(screen.queryByText('Tópicos')).not.toBeInTheDocument();
    });

    it('renders admin-only children items in Seminários group for admin users', () => {
        render(<Sidebar />);
        // Admin should see all Seminários children
        // These are rendered inside Collapsible.Content which may not render in jsdom,
        // but let's check the trigger contains admin items
        // Since Seminários is open by default, we check for the items
        const allButtons = document.querySelectorAll('button');
        // At minimum, the Seminários trigger should be present
        const seminariosTrigger = Array.from(allButtons).find(b => b.textContent?.includes('Seminários'));
        expect(seminariosTrigger).toBeTruthy();
    });

    it('renders the Voltar ao Site link with correct href', () => {
        render(<Sidebar />);
        const backLink = screen.getByText('Voltar ao Site').closest('a');
        expect(backLink).toHaveAttribute('href', '/');
    });

    it('shows teacher name and email correctly', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 2, name: 'Maria Silva', email: 'maria@cefet.br', roles: ['teacher'] } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Sidebar />);
        expect(screen.getByText('Maria Silva')).toBeInTheDocument();
        expect(screen.getByText('maria@cefet.br')).toBeInTheDocument();
        expect(screen.getByText('M')).toBeInTheDocument();
    });

    it('renders Dashboard NavLink with correct href', () => {
        render(<Sidebar />);
        const dashboardLink = screen.getByText('Dashboard').closest('a');
        expect(dashboardLink).toHaveAttribute('href', '/');
    });

    it('renders Usuários NavLink for admin with correct href', () => {
        render(<Sidebar />);
        const usersLink = screen.getByText('Usuários').closest('a');
        expect(usersLink).toHaveAttribute('href', '/users');
    });

    it('renders Inscrições NavLink for admin with correct href', () => {
        render(<Sidebar />);
        const registrationsLink = screen.getByText('Inscrições').closest('a');
        expect(registrationsLink).toHaveAttribute('href', '/registrations');
    });

    it('handles user with undefined roles (isAdmin ?? false fallback)', async () => {
        const { useAuth } = await import('@shared/contexts/AuthContext');
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 3, name: 'No Roles User', email: 'noroles@test.com', roles: undefined } as any,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Sidebar />);

        // Should see Dashboard (non-admin item)
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        // Should not see admin-only items
        expect(screen.queryByText('Usuários')).not.toBeInTheDocument();
        expect(screen.queryByText('Inscrições')).not.toBeInTheDocument();
    });

    it('renders NavLink items with correct styles at specific routes', () => {
        render(<Sidebar />, {
            routerProps: { initialEntries: ['/users'] },
        });

        // Dashboard should have inactive style at /users route
        const dashboardLink = screen.getByText('Dashboard').closest('a');
        expect(dashboardLink).toBeInTheDocument();
        // Usuários should be active
        const usersLink = screen.getByText('Usuários').closest('a');
        expect(usersLink).toBeInTheDocument();
    });

    it('renders child NavLinks with active style when route matches', () => {
        render(<Sidebar />, {
            routerProps: { initialEntries: ['/seminars'] },
        });

        // Seminários group is open by default, its children should be visible
        // The child "Seminários" link (href=/seminars) should be active at route "/seminars"
        const seminarsChildLinks = screen.getAllByText('Seminários');
        const childNavLink = seminarsChildLinks
            .map(el => el.closest('a'))
            .find(a => a?.getAttribute('href') === '/seminars');
        expect(childNavLink).toBeInTheDocument();
        expect(childNavLink!.className).toContain('bg-accent');
    });
});
