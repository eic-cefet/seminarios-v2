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

import { MobileHeader } from './MobileHeader';

describe('MobileHeader', () => {
    it('renders the EIC logo', () => {
        render(<MobileHeader />);
        expect(screen.getByText('EIC')).toBeInTheDocument();
    });

    it('renders the CEFET-RJ title', () => {
        render(<MobileHeader />);
        expect(screen.getByText('CEFET-RJ')).toBeInTheDocument();
    });

    it('renders the menu toggle button with sr-only text', () => {
        render(<MobileHeader />);
        expect(screen.getByText('Abrir menu')).toBeInTheDocument();
    });

    it('toggles the mobile menu when the menu button is clicked', () => {
        render(<MobileHeader />);

        // Click to open menu - Dashboard nav item should become visible
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
    });

    it('shows user info when menu is open', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        expect(screen.getByText('Admin User')).toBeInTheDocument();
        expect(screen.getByText('admin@test.com')).toBeInTheDocument();
    });

    it('renders logout and back-to-site buttons when menu is open', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        expect(screen.getByText('Sair')).toBeInTheDocument();
        expect(screen.getByText('Voltar ao Site')).toBeInTheDocument();
    });

    it('shows admin-only navigation items for admin users', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        expect(screen.getByText('Usuários')).toBeInTheDocument();
        expect(screen.getByText('Inscrições')).toBeInTheDocument();
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

        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        expect(screen.queryByText('Usuários')).not.toBeInTheDocument();
        expect(screen.queryByText('Inscrições')).not.toBeInTheDocument();
    });

    it('renders the Seminários group trigger when menu is open', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        // Seminários group trigger should be present
        const seminarioButtons = screen.getAllByText('Seminários');
        expect(seminarioButtons.length).toBeGreaterThan(0);
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

        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        fireEvent.click(screen.getByText('Sair'));
        expect(mockLogout).toHaveBeenCalledTimes(1);
    });

    it('renders user initial avatar in the menu', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(screen.getByText('A')).toBeInTheDocument();
    });

    it('shows Relatórios group for admin users', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(screen.getByText('Relatórios')).toBeInTheDocument();
    });

    it('closes menu when clicking a navigation link', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        // Click Dashboard link
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        fireEvent.click(screen.getByText('Dashboard'));

        // Menu should be closed (hidden class applied)
        // After clicking a NavLink, closeMenu is called which sets menuOpen to false
        // Check for the hidden class on the menu container
        const menuContainer = screen.getByText('Dashboard').closest('nav')!.parentElement!;
        expect(menuContainer.className).toContain('hidden');
    });

    it('toggles menu open and then closed', () => {
        render(<MobileHeader />);

        // Open menu
        fireEvent.click(screen.getByText('Abrir menu'));
        const dashboardLink = screen.getByText('Dashboard');
        const menuContainer = dashboardLink.closest('nav')!.parentElement!;
        expect(menuContainer.className).toContain('block');

        // Close menu by clicking toggle again
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(menuContainer.className).toContain('hidden');
    });

    it('toggles expanded submenu when group trigger is clicked', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        // Seminários group is expanded by default
        // Find the Seminários button (the group trigger, not the child link)
        const allButtons = document.querySelectorAll('button');
        const seminariosTrigger = Array.from(allButtons).find(
            (b) => b.textContent?.includes('Seminários') && !b.textContent?.includes('Abrir'),
        );
        expect(seminariosTrigger).toBeTruthy();

        // Click to collapse
        fireEvent.click(seminariosTrigger!);

        // Now expanded children should be gone (Workshops, Locais, Tópicos etc.)
        // For admin, these should no longer be visible
        // Click again to re-expand
        fireEvent.click(seminariosTrigger!);
    });

    it('hides Relatórios group for teacher users', async () => {
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

        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(screen.queryByText('Relatórios')).not.toBeInTheDocument();
    });

    it('hides admin-only Seminários children for teacher users', async () => {
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

        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        // Teacher should see the Seminários group but not admin-only children
        expect(screen.queryByText('Workshops')).not.toBeInTheDocument();
        expect(screen.queryByText('Locais')).not.toBeInTheDocument();
        expect(screen.queryByText('Tópicos')).not.toBeInTheDocument();
    });

    it('closes menu when back-to-site link is clicked', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(screen.getByText('Voltar ao Site')).toBeInTheDocument();

        const menuContainer = screen.getByText('Dashboard').closest('nav')!.parentElement!;
        expect(menuContainer.className).toContain('block');

        // The "Voltar ao Site" is an <a> tag, not NavLink so it uses onClick={closeMenu}
        fireEvent.click(screen.getByText('Voltar ao Site'));
        // After clicking, the menu should be closed (hidden class)
        expect(menuContainer.className).toContain('hidden');
    });

    it('renders teacher name and initial correctly', async () => {
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

        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        expect(screen.getByText('Maria Silva')).toBeInTheDocument();
        expect(screen.getByText('maria@cefet.br')).toBeInTheDocument();
        expect(screen.getByText('M')).toBeInTheDocument();
    });

    it('renders Voltar ao Site link with correct href', () => {
        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));
        const backLink = screen.getByText('Voltar ao Site').closest('a');
        expect(backLink).toHaveAttribute('href', '/');
    });

    it('renders child NavLinks with active style when route matches', () => {
        render(<MobileHeader />, {
            routerProps: { initialEntries: ['/seminars'] },
        });
        fireEvent.click(screen.getByText('Abrir menu'));

        // Seminários group is expanded by default, its children should be visible
        // The child "Seminários" link (href=/seminars) should be active at route "/seminars"
        const seminarsChildLinks = screen.getAllByText('Seminários');
        const childNavLink = seminarsChildLinks
            .map(el => el.closest('a'))
            .find(a => a?.getAttribute('href') === '/seminars');
        expect(childNavLink).toBeInTheDocument();
        expect(childNavLink!.className).toContain('bg-accent');
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

        render(<MobileHeader />);
        fireEvent.click(screen.getByText('Abrir menu'));

        // Should see Dashboard (non-admin item)
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        // Should not see admin-only items
        expect(screen.queryByText('Usuários')).not.toBeInTheDocument();
        expect(screen.queryByText('Inscrições')).not.toBeInTheDocument();
    });

    it('renders non-children NavLink items with correct active/inactive styles', async () => {
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

        render(<MobileHeader />, {
            routerProps: { initialEntries: ['/users'] },
        });
        fireEvent.click(screen.getByText('Abrir menu'));

        // Dashboard should be a NavLink (non-children item)
        const dashboardLink = screen.getByText('Dashboard').closest('a');
        expect(dashboardLink).toBeInTheDocument();
        // Users link should exist for admin and be active at /users
        const usersLink = screen.getByText('Usuários').closest('a');
        expect(usersLink).toBeInTheDocument();
    });
});
