import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@/test/test-utils';
import NotFound from './NotFound';

vi.mock('@shared/contexts/AuthContext', () => ({
    useAuth: vi.fn(() => ({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

describe('NotFound', () => {
    it('renders "404"', () => {
        render(<NotFound />);
        expect(screen.getByText('404')).toBeInTheDocument();
    });

    it('renders "Página não encontrada"', () => {
        render(<NotFound />);
        expect(screen.getByRole('heading', { name: /página não encontrada/i })).toBeInTheDocument();
    });

    it('renders link to home with "Voltar para o início"', () => {
        render(<NotFound />);
        const link = screen.getByRole('link', { name: /voltar para o início/i });
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/');
    });
});
