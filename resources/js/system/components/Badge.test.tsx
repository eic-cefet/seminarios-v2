import { describe, it, expect } from 'vitest';
import { render, screen } from '@/test/test-utils';
import { Badge } from './Badge';

describe('Badge', () => {
    it('renders children', () => {
        render(<Badge>Test Badge</Badge>);
        expect(screen.getByText('Test Badge')).toBeInTheDocument();
    });

    it('applies default variant', () => {
        render(<Badge>Default</Badge>);
        const badge = screen.getByText('Default');
        expect(badge).toHaveClass('bg-gray-100', 'text-gray-700');
    });

    it('applies success variant style class', () => {
        render(<Badge variant="success">Success</Badge>);
        const badge = screen.getByText('Success');
        expect(badge).toHaveClass('bg-green-100', 'text-green-700');
    });

    it('applies warning variant style class', () => {
        render(<Badge variant="warning">Warning</Badge>);
        const badge = screen.getByText('Warning');
        expect(badge).toHaveClass('bg-yellow-100', 'text-yellow-700');
    });

    it('applies error variant style class', () => {
        render(<Badge variant="error">Error</Badge>);
        const badge = screen.getByText('Error');
        expect(badge).toHaveClass('bg-red-100', 'text-red-700');
    });

    it('applies expired variant style class', () => {
        render(<Badge variant="expired">Expired</Badge>);
        const badge = screen.getByText('Expired');
        expect(badge).toHaveClass('bg-gray-200', 'text-gray-500');
    });

    it('applies custom className', () => {
        render(<Badge className="custom-class">Custom</Badge>);
        const badge = screen.getByText('Custom');
        expect(badge).toHaveClass('custom-class');
    });
});
