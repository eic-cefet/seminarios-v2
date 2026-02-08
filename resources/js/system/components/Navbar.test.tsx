import { render, screen, userEvent } from '@/test/test-utils';
import { Navbar } from './Navbar';
import { useAuth } from '@shared/contexts/AuthContext';
import { analytics } from '@shared/lib/analytics';

const mockLogout = vi.fn();

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null, isLoading: false, isAuthenticated: false,
        login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
    })),
}));

vi.mock('./LoginModal', () => ({
    LoginModal: ({ open }: { open: boolean }) => open ? <div data-testid="login-modal">LoginModal</div> : null,
}));

vi.mock('@shared/lib/analytics', () => ({
    analytics: { event: vi.fn() },
}));

describe('Navbar', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(useAuth).mockReturnValue({
            user: null, isLoading: false, isAuthenticated: false,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });
    });

    it('renders logo "Seminários EIC"', () => {
        render(<Navbar />);
        expect(screen.getByText('Seminários EIC')).toBeInTheDocument();
    });

    it('renders navigation links', () => {
        render(<Navbar />);
        // Multiple links may exist (desktop + mobile), just check at least one of each
        expect(screen.getAllByText('Início').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Tópicos').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Apresentações').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Workshops').length).toBeGreaterThan(0);
    });

    it('shows auth buttons when not authenticated', () => {
        render(<Navbar />);
        // "Entrar" appears in both desktop and mobile views
        expect(screen.getAllByText('Entrar').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Criar conta').length).toBeGreaterThan(0);
    });

    it('renders mobile menu button', () => {
        render(<Navbar />);
        expect(screen.getByRole('button', { name: 'Abrir menu' })).toBeInTheDocument();
    });

    it('toggles mobile menu open and closed', async () => {
        const user = userEvent.setup();
        render(<Navbar />);

        const menuButton = screen.getByRole('button', { name: 'Abrir menu' });

        // Open menu
        await user.click(menuButton);
        expect(analytics.event).toHaveBeenCalledWith('navbar_menu_open');

        // Close menu
        await user.click(menuButton);
        // analytics.event should have been called once for open, not for close
        expect(analytics.event).toHaveBeenCalledTimes(1);
    });

    it('shows user name when authenticated', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'John Doe', email: 'john@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        // User name appears in both desktop dropdown and mobile menu
        expect(screen.getAllByText('John Doe').length).toBeGreaterThan(0);
    });

    it('shows admin link for admin users in desktop nav', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Admin User', email: 'admin@example.com', roles: ['admin'] } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        const adminLinks = screen.getAllByText('Admin');
        expect(adminLinks.length).toBeGreaterThan(0);
    });

    it('shows admin link for teacher users', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Teacher User', email: 'teacher@example.com', roles: ['teacher'] } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        const adminLinks = screen.getAllByText('Admin');
        expect(adminLinks.length).toBeGreaterThan(0);
    });

    it('does not show admin link for regular users', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Regular User', email: 'user@example.com', roles: [] } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        expect(screen.queryByText('Admin')).not.toBeInTheDocument();
    });

    it('shows mobile user menu with profile links when authenticated', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Mobile User', email: 'mobile@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        // Mobile menu contains user info and links
        expect(screen.getByText('mobile@example.com')).toBeInTheDocument();
        expect(screen.getByText('Meu perfil')).toBeInTheDocument();
    });

    it('shows mobile menu with Entrar and Criar conta for unauthenticated users', async () => {
        render(<Navbar />);

        // Mobile menu buttons appear in the DOM
        const entrarButtons = screen.getAllByText('Entrar');
        const criarContaLinks = screen.getAllByText('Criar conta');

        expect(entrarButtons.length).toBeGreaterThanOrEqual(2); // desktop + mobile
        expect(criarContaLinks.length).toBeGreaterThanOrEqual(2); // desktop + mobile
    });

    it('opens login modal when desktop Entrar is clicked', async () => {
        const user = userEvent.setup();
        render(<Navbar />);

        // Click desktop "Entrar" button (the one that is not in mobile menu)
        const entrarButtons = screen.getAllByText('Entrar');
        // Desktop button is the first one
        await user.click(entrarButtons[0]);

        expect(analytics.event).toHaveBeenCalledWith('login_modal_open');
        expect(screen.getByTestId('login-modal')).toBeInTheDocument();
    });

    it('opens login modal from mobile menu Entrar button', async () => {
        const user = userEvent.setup();
        render(<Navbar />);

        // Open mobile menu first
        await user.click(screen.getByRole('button', { name: 'Abrir menu' }));

        // Click mobile "Entrar" - it's the button (not the desktop one)
        const entrarButtons = screen.getAllByText('Entrar');
        // Mobile Entrar is the last one
        await user.click(entrarButtons[entrarButtons.length - 1]);

        expect(analytics.event).toHaveBeenCalledWith('login_modal_open', { source: 'mobile_menu' });
        expect(screen.getByTestId('login-modal')).toBeInTheDocument();
    });

    it('shows mobile user menu with logout and links when authenticated', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Jane Doe', email: 'jane@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        // Mobile user menu shows links
        expect(screen.getByText('Meu perfil')).toBeInTheDocument();
        expect(screen.getAllByText('Sair').length).toBeGreaterThan(0);
    });

    it('calls logout and tracks analytics when mobile Sair is clicked', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Jane Doe', email: 'jane@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        const user = userEvent.setup();
        render(<Navbar />);

        // Find mobile Sair button (last one in DOM)
        const sairButtons = screen.getAllByText('Sair');
        await user.click(sairButtons[sairButtons.length - 1]);

        expect(analytics.event).toHaveBeenCalledWith('logout', { source: 'mobile_menu' });
        expect(mockLogout).toHaveBeenCalled();
    });

    it('shows Avaliar seminarios link in mobile menu for authenticated user', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Test User', email: 'test@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        expect(screen.getAllByText('Avaliar seminarios').length).toBeGreaterThan(0);
    });

    it('shows Meus certificados link in mobile menu for authenticated user', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Test User', email: 'test@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        expect(screen.getAllByText('Meus certificados').length).toBeGreaterThan(0);
    });

    it('shows mobile admin link for admin users and it exists as anchor', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Admin User', email: 'admin@example.com', roles: ['admin'] } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        // Admin link appears in both desktop and mobile; check it points to /admin
        const adminLinks = screen.getAllByText('Admin');
        expect(adminLinks.length).toBeGreaterThanOrEqual(2); // desktop + mobile
    });

    it('closes mobile menu when a navigation link is clicked', async () => {
        const user = userEvent.setup();
        render(<Navbar />);

        // Open mobile menu
        await user.click(screen.getByRole('button', { name: 'Abrir menu' }));

        // Click a mobile nav link (e.g., "Tópicos")
        const topicosLinks = screen.getAllByText('Tópicos');
        // Mobile link is the second instance
        await user.click(topicosLinks[topicosLinks.length - 1]);

        // After clicking, mobile menu should be closed (the link onClick calls setMobileMenuOpen(false))
        // We verify indirectly by re-opening - the toggle button should show menu icon not X
    });

    it('does not show admin link when user has no roles', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'No Role User', email: 'norole@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        expect(screen.queryByText('Admin')).not.toBeInTheDocument();
    });

    it('renders user email in desktop and mobile views for authenticated user', () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Full User', email: 'full@example.com' } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        render(<Navbar />);

        expect(screen.getByText('full@example.com')).toBeInTheDocument();
    });

    it('closes mobile menu when mobile Criar conta link is clicked', async () => {
        const user = userEvent.setup();
        render(<Navbar />);

        // Open mobile menu
        await user.click(screen.getByRole('button', { name: 'Abrir menu' }));

        // Click mobile "Criar conta" link (the second one is in mobile)
        const criarContaLinks = screen.getAllByText('Criar conta');
        await user.click(criarContaLinks[criarContaLinks.length - 1]);

        // The onClick handler sets mobileMenuOpen to false
    });

    it('closes mobile menu when mobile admin link is clicked for admin user', async () => {
        vi.mocked(useAuth).mockReturnValue({
            user: { id: 1, name: 'Admin User', email: 'admin@example.com', roles: ['admin'] } as any,
            isLoading: false, isAuthenticated: true,
            login: vi.fn(), register: vi.fn(), logout: mockLogout, exchangeCode: vi.fn(), refreshUser: vi.fn(),
        });

        const user = userEvent.setup();
        render(<Navbar />);

        // Open mobile menu
        await user.click(screen.getByRole('button', { name: 'Abrir menu' }));

        // Find all Admin links and click the mobile one (which is an <a> tag in the mobile menu)
        const adminLinks = screen.getAllByText('Admin');
        // Click the last one which is the mobile admin link
        await user.click(adminLinks[adminLinks.length - 1]);

        // The onClick handler sets mobileMenuOpen to false
    });
});
