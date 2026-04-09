import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@/test/test-utils';
import { Layout } from './Layout';

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
}));

vi.mock('./Navbar', () => ({
    Navbar: () => <nav data-testid="navbar">Navbar</nav>,
}));

vi.mock('./Footer', () => ({
    Footer: () => <footer data-testid="footer">Footer</footer>,
}));

vi.mock('@shared/components/Favicon', () => ({
    Favicon: () => null,
}));

describe('Layout', () => {
    it('renders children content', () => {
        render(
            <Layout>
                <div>Test Content</div>
            </Layout>
        );

        expect(screen.getByText('Test Content')).toBeInTheDocument();
    });

    it('renders Navbar', () => {
        render(
            <Layout>
                <div>Content</div>
            </Layout>
        );

        expect(screen.getByTestId('navbar')).toBeInTheDocument();
    });

    it('renders Footer', () => {
        render(
            <Layout>
                <div>Content</div>
            </Layout>
        );

        expect(screen.getByTestId('footer')).toBeInTheDocument();
    });

    it('renders navigation (Seminários EIC text from Navbar)', () => {
        render(
            <Layout>
                <div>Content</div>
            </Layout>
        );

        expect(screen.getByText('Navbar')).toBeInTheDocument();
    });
});
