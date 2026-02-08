import { render, screen } from '@/test/test-utils';
import NotFound from './NotFound';

describe('Admin NotFound', () => {
    it('renders 404 text', () => {
        render(<NotFound />);
        expect(screen.getByText('404')).toBeInTheDocument();
    });

    it('renders "Página não encontrada"', () => {
        render(<NotFound />);
        expect(screen.getByText('Página não encontrada')).toBeInTheDocument();
    });

    it('renders link to dashboard', () => {
        render(<NotFound />);
        expect(screen.getByText('Voltar para o Dashboard')).toBeInTheDocument();
    });
});
