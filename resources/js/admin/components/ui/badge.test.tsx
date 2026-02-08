import { render, screen } from '@/test/test-utils';
import { Badge } from './badge';

describe('Badge', () => {
    it('renders children', () => {
        render(<Badge>Active</Badge>);
        expect(screen.getByText('Active')).toBeInTheDocument();
    });

    it('applies default variant', () => {
        render(<Badge>Default</Badge>);
        expect(screen.getByText('Default')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Badge className="custom">Test</Badge>);
        expect(screen.getByText('Test')).toHaveClass('custom');
    });

    it('renders with secondary variant', () => {
        render(<Badge variant="secondary">Secondary</Badge>);
        expect(screen.getByText('Secondary')).toBeInTheDocument();
    });

    it('renders with destructive variant', () => {
        render(<Badge variant="destructive">Error</Badge>);
        expect(screen.getByText('Error')).toBeInTheDocument();
    });
});
