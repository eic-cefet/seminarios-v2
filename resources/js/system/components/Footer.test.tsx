import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen } from '@/test/test-utils';
import { Footer } from './Footer';

describe('Footer', () => {

    beforeEach(() => {
        // Mock Date to return a fixed year
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-01-15'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders navigation links', () => {
        render(<Footer />);

        expect(screen.getByRole('link', { name: 'Tópicos' })).toHaveAttribute('href', '/topicos');
        expect(screen.getByRole('link', { name: 'Apresentações' })).toHaveAttribute('href', '/apresentacoes');
        expect(screen.getByRole('link', { name: 'Workshops' })).toHaveAttribute('href', '/workshops');
        expect(screen.getByRole('link', { name: 'Reportar Bug' })).toHaveAttribute('href', '/reportar-bug');
    });

    it('renders copyright with current year and "CEFET-RJ"', () => {
        render(<Footer />);

        expect(screen.getByText(/2026 CEFET-RJ/)).toBeInTheDocument();
        expect(screen.getByText(/Escola de Informática e Computação/)).toBeInTheDocument();
    });
});
