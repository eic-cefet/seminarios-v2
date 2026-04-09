import { render, screen } from '@/test/test-utils';
import { createUser } from '@/test/factories';
import Profile from './Profile';

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

vi.mock('../components/profile/ProfileInfoSection', () => ({
    ProfileInfoSection: () => <div>ProfileInfoSection</div>,
}));

vi.mock('../components/profile/StudentDataSection', () => ({
    StudentDataSection: () => <div>StudentDataSection</div>,
}));

vi.mock('../components/profile/PasswordSection', () => ({
    PasswordSection: () => <div>PasswordSection</div>,
}));

vi.mock('../components/profile/RegistrationsSection', () => ({
    RegistrationsSection: () => <div>RegistrationsSection</div>,
}));

vi.mock('../components/profile/CertificatesSection', () => ({
    CertificatesSection: () => <div>CertificatesSection</div>,
}));

import { useAuth } from '@shared/contexts/AuthContext';

describe('Profile', () => {
    it('redirects to login when user is null', () => {
        render(<Profile />);
        // When Navigate is rendered, profile content is not shown
        expect(screen.queryByRole('heading', { name: /meu perfil/i })).not.toBeInTheDocument();
    });

    it('shows loading spinner when isLoading=true', () => {
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

        const { container } = render(<Profile />);
        // Loader2 renders an SVG with animate-spin class
        expect(container.querySelector('.animate-spin')).toBeInTheDocument();
    });

    it('renders "Meu Perfil" heading when authenticated', () => {
        const user = createUser();
        vi.mocked(useAuth).mockReturnValue({
            user,
            isLoading: false,
            isAuthenticated: true,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
        });

        render(<Profile />);

        expect(screen.getByRole('heading', { name: /meu perfil/i })).toBeInTheDocument();
        expect(screen.getByText('ProfileInfoSection')).toBeInTheDocument();
        expect(screen.getByText('StudentDataSection')).toBeInTheDocument();
        expect(screen.getByText('PasswordSection')).toBeInTheDocument();
        expect(screen.getByText('RegistrationsSection')).toBeInTheDocument();
        expect(screen.getByText('CertificatesSection')).toBeInTheDocument();
    });
});
